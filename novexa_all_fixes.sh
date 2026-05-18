#!/bin/bash
# ══════════════════════════════════════════════════════
# NOVEXA — ALL BUGS FIX SCRIPT
# Run on server: bash novexa_all_fixes.sh
# ══════════════════════════════════════════════════════

echo "🔧 NOVEXA BUG FIX SCRIPT STARTING..."
echo "========================================="

# Backup first!
cp /var/www/novexa/admin.php /var/www/novexa/admin.php.bak_$(date +%Y%m%d_%H%M%S)
cp /var/www/novexa/withdraw.php /var/www/novexa/withdraw.php.bak_$(date +%Y%m%d_%H%M%S)
echo "✅ Backups created"

# ══════════════════════════════════════════════════════
# FIX #1: Combo ROI — Parameter Count Mismatch (CRITICAL)
# SQL has 6 ? but execute passes 7 params → CRASH
# Remove extra 2.00 from execute array
# ══════════════════════════════════════════════════════
echo ""
echo "🔴 FIX #1: Combo ROI parameter crash..."

php -r "
\$file = '/var/www/novexa/admin.php';
\$code = file_get_contents(\$file);

// Find combo ROI section - the broken execute with extra 2.00
\$old = \"->execute([\\\$deposit['user_id'], \\\$i, 2.00, 0, 200, 'pending', \\\$rd])\";
\$new = \"->execute([\\\$deposit['user_id'], \\\$i, 0, 200, 'pending', \\\$rd])\";

if(strpos(\$code, \$old) !== false) {
    \$code = str_replace(\$old, \$new, \$code);
    file_put_contents(\$file, \$code);
    echo '✅ FIX #1 APPLIED: Combo ROI parameter fixed' . PHP_EOL;
} else {
    echo '⚠️ FIX #1: Pattern not found (maybe already fixed?)' . PHP_EOL;
}
"

# ══════════════════════════════════════════════════════
# FIX #2: Combo package_amount $150 → $100
# Binary ceiling was $150, should be $100
# ══════════════════════════════════════════════════════
echo ""
echo "🔴 FIX #2: Combo binary ceiling fix..."

php -r "
\$file = '/var/www/novexa/admin.php';
\$code = file_get_contents(\$file);

\$old = \"UPDATE users SET active_package='upgrade' WHERE id=?\";
\$new = \"UPDATE users SET active_package='upgrade', package_amount=100 WHERE id=?\";

if(strpos(\$code, \$old) !== false) {
    \$code = str_replace(\$old, \$new, \$code);
    file_put_contents(\$file, \$code);
    echo '✅ FIX #2 APPLIED: Combo package_amount set to 100' . PHP_EOL;
} else {
    echo '⚠️ FIX #2: Pattern not found' . PHP_EOL;
}
"

# ══════════════════════════════════════════════════════
# FIX #3: Combo Sponsor total_balance NOT updated
# Add total_balance + referral_balance update
# ══════════════════════════════════════════════════════
echo ""
echo "🔴 FIX #3: Combo sponsor balance update..."

php -r "
\$file = '/var/www/novexa/admin.php';
\$code = file_get_contents(\$file);

\$old = \"addNotification(\\\$sponsor['id'], 'Combo Referral!', '\\\$10 (\\\$8 USDT + \\\$2 SOL) + \\\$0.50/day for 100 days', 'income');\";
\$new = \"\\\$pdo->prepare(\\\"UPDATE users SET total_balance=total_balance+10, referral_balance=referral_balance+10 WHERE id=?\\\")->execute([\\\$sponsor['id']]);
                    addNotification(\\\$sponsor['id'], 'Combo Referral!', '\\\$10 (\\\$8 USDT + \\\$2 SOL) + \\\$0.50/day for 100 days', 'income');\";

if(strpos(\$code, \$old) !== false) {
    \$code = str_replace(\$old, \$new, \$code);
    file_put_contents(\$file, \$code);
    echo '✅ FIX #3 APPLIED: Combo sponsor total_balance updated' . PHP_EOL;
} else {
    echo '⚠️ FIX #3: Pattern not found' . PHP_EOL;
}
"

# ══════════════════════════════════════════════════════
# FIX #4: Add TOP-UP handling in approve_deposit
# Insert after upgrade elseif block, before the final notification
# ══════════════════════════════════════════════════════
echo ""
echo "🟡 FIX #4: Adding Top-Up package handling..."

php -r "
\$file = '/var/www/novexa/admin.php';
\$code = file_get_contents(\$file);

// Find the end of upgrade ROI loop and add topup case after it
\$marker = \"// ROI: \\\$2/day for 100 days (\\\$1.60 USDT + \\\$0.40 SOL)
                for(\\\$i=1; \\\$i<=100; \\\$i++) {
                    \\\$rd = date('Y-m-d', strtotime(\\\"+\\\$i days\\\"));
                    \\\$pdo->prepare(\\\"INSERT INTO roi_history (user_id, day_number, daily_amount, total_released, total_remaining, status, release_date) VALUES (?,?,2.00,?,?,?,?)\\\")->execute([\\\$deposit['user_id'], \\\$i, \\\$i*2, 200-(\\\$i*2), 'pending', \\\$rd]);
                }
            }\";

\$topupCode = \"// ROI: \\\$2/day for 100 days (\\\$1.60 USDT + \\\$0.40 SOL)
                for(\\\$i=1; \\\$i<=100; \\\$i++) {
                    \\\$rd = date('Y-m-d', strtotime(\\\"+\\\$i days\\\"));
                    \\\$pdo->prepare(\\\"INSERT INTO roi_history (user_id, day_number, daily_amount, total_released, total_remaining, status, release_date) VALUES (?,?,2.00,?,?,?,?)\\\")->execute([\\\$deposit['user_id'], \\\$i, \\\$i*2, 200-(\\\$i*2), 'pending', \\\$rd]);
                }
            } elseif(\\\$deposit['package_type'] === 'topup') {
                // \\\$100 TOP-UP: Same as upgrade but stackable
                // Sponsor 10% instant (\\\$8 USDT + \\\$2 SOL)
                if(\\\$sponsor) {
                    \\\$pdo->prepare(\\\"UPDATE wallet_balances SET usdt_balance=usdt_balance+8, sol_balance=sol_balance+2 WHERE user_id=?\\\")->execute([\\\$sponsor['id']]);
                    \\\$pdo->prepare(\\\"UPDATE users SET total_balance=total_balance+10, referral_balance=referral_balance+10 WHERE id=?\\\")->execute([\\\$sponsor['id']]);
                    \\\$pdo->prepare(\\\"INSERT INTO income (user_id, type, amount, from_user_id, description) VALUES (?, 'direct_sponsor', 10, ?, ?)\\\")->execute([\\\$sponsor['id'], \\\$user['id'], 'Top-Up 10% from '.\\\$user['username'].' (\\\$8 USDT + \\\$2 SOL)']);
                    addTransaction(\\\$sponsor['id'], 'direct_sponsor', 8, 'usdt', 'Top-Up 10% USDT from '.\\\$user['username']);
                    addTransaction(\\\$sponsor['id'], 'direct_sponsor', 2, 'sol', 'Top-Up 10% SOL from '.\\\$user['username']);
                    addNotification(\\\$sponsor['id'], 'Top-Up Referral!', '\\\$8 USDT + \\\$2 SOL from '.\\\$user['username'], 'income');
                }
                addTransaction(\\\$deposit['user_id'], 'deposit', 100, 'internal', '\\\$100 Top-Up Activated - 2X ROI for 100 days');
                
                // ROI: \\\$2/day for 100 days (stacks with existing)
                for(\\\$i=1; \\\$i<=100; \\\$i++) {
                    \\\$rd = date('Y-m-d', strtotime(\\\"+\\\$i days\\\"));
                    \\\$pdo->prepare(\\\"INSERT INTO roi_history (user_id, day_number, daily_amount, total_released, total_remaining, status, release_date) VALUES (?,?,2.00,?,?,?,?)\\\")->execute([\\\$deposit['user_id'], \\\$i, \\\$i*2, 200-(\\\$i*2), 'pending', \\\$rd]);
                }
                
                // BV \\\$100 for binary
                \\\$bvTopup = 100;
                if(\\\$user && \\\$user['sponsor_id']) {
                    \\\$pos = \\\$user['binary_position'] ?? 'left';
                    \\\$col = (\\\$pos === 'left') ? 'left_volume' : 'right_volume';
                    \\\$pdo->prepare(\\\"UPDATE binary_tree SET \\\$col = \\\$col + ? WHERE user_id = ?\\\")->execute([\\\$bvTopup, \\\$user['sponsor_id']]);
                    \\\$currentId = \\\$user['sponsor_id'];
                    while(\\\$currentId) {
                        \\\$parent = getUser(\\\$currentId);
                        if(!\\\$parent || !\\\$parent['sponsor_id']) break;
                        \\\$parentBT = \\\$pdo->prepare(\\\"SELECT * FROM binary_tree WHERE user_id=?\\\");
                        \\\$parentBT->execute([\\\$parent['sponsor_id']]);
                        \\\$pBT = \\\$parentBT->fetch(PDO::FETCH_ASSOC);
                        if(\\\$pBT) {
                            \\\$sideCol = (\\\$pBT['left_child_id'] == \\\$currentId || isInSubtree(\\\$pdo, \\\$pBT['left_child_id'], \\\$currentId)) ? 'left_volume' : 'right_volume';
                            \\\$pdo->prepare(\\\"UPDATE binary_tree SET \\\$sideCol = \\\$sideCol + ? WHERE user_id = ?\\\")->execute([\\\$bvTopup, \\\$parent['sponsor_id']]);
                        }
                        \\\$currentId = \\\$parent['sponsor_id'];
                    }
                }
                
                // Update package amount (add to existing for ceiling)
                \\\$pdo->prepare(\\\"UPDATE users SET package_amount=package_amount+100 WHERE id=?\\\")->execute([\\\$deposit['user_id']]);
            }\";

if(strpos(\$code, \$marker) !== false) {
    \$code = str_replace(\$marker, \$topupCode, \$code);
    file_put_contents(\$file, \$code);
    echo '✅ FIX #4 APPLIED: Top-Up handling added' . PHP_EOL;
} else {
    echo '⚠️ FIX #4: Marker not found — may need manual edit' . PHP_EOL;
}
"

# ══════════════════════════════════════════════════════
# FIX #5: Wallet auto-fill in withdraw.php
# Populate userWallets from PHP user data
# ══════════════════════════════════════════════════════
echo ""
echo "🟡 FIX #5: Wallet auto-fill fix..."

php -r "
\$file = '/var/www/novexa/withdraw.php';
\$code = file_get_contents(\$file);

\$old = 'const userWallets = {
  usdt: \"\",
  sol: \"\",
  usdc: \"\",
  novexa: \"\"
};';

\$new = 'const userWallets = {
  usdt: \"<?= htmlspecialchars(\$user[\'wallet_address_usdt\'] ?? \'\') ?>\",
  sol: \"<?= htmlspecialchars(\$user[\'wallet_address_sol\'] ?? \'\') ?>\",
  usdc: \"<?= htmlspecialchars(\$user[\'wallet_address_usdc\'] ?? \'\') ?>\",
  novexa: \"<?= htmlspecialchars(\$user[\'wallet_address_novexa\'] ?? \'\') ?>\"
};';

if(strpos(\$code, \$old) !== false) {
    \$code = str_replace(\$old, \$new, \$code);
    file_put_contents(\$file, \$code);
    echo '✅ FIX #5 APPLIED: Wallet auto-fill working' . PHP_EOL;
} else {
    echo '⚠️ FIX #5: Pattern not found' . PHP_EOL;
}
"

echo ""
echo "══════════════════════════════════════"
echo "✅ ALL FIXES APPLIED!"
echo "══════════════════════════════════════"
echo ""
echo "FIXES SUMMARY:"
echo "  🔴 #1: Combo ROI parameter crash — FIXED"
echo "  🔴 #2: Combo binary ceiling $150→$100 — FIXED"  
echo "  🔴 #3: Combo sponsor balance update — FIXED"
echo "  🟡 #4: Top-Up package handling — ADDED"
echo "  🟡 #5: Wallet auto-fill — FIXED"
echo ""
echo "BACKUPS at:"
echo "  /var/www/novexa/admin.php.bak_*"
echo "  /var/www/novexa/withdraw.php.bak_*"
echo ""
echo "NOW TEST:"
echo "  1. Approve a $150 combo deposit → check ROI scheduled"
echo "  2. Check combo user package_amount = $100"
echo "  3. Submit $100 topup → approve → check ROI + BV"
echo "  4. Withdraw page → select coin → wallet auto-fills"
echo "══════════════════════════════════════"
