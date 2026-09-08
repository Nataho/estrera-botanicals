<?php
// account tab - activity & order logs
// if admin, shows store-wide activity & orders log. if customer, shows personal order logs
?>
<section class="account-panel">
    <div class="panel-header-row">
        <div>
            <h2><?= $user->is_admin() ? 'Store Activity & Order Logs' : 'My Order History' ?></h2>
            <p class="subtext">
                <?= $user->is_admin() 
                    ? 'Review customer orders, transaction dates, and fulfillment status.' 
                    : 'Track your recent purchases and delivery details.' ?>
            </p>
        </div>
    </div>

    <!-- Logs / Orders Table -->
    <div class="table-responsive">
        <table class="account-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <?php if ($user->is_admin()): ?>
                        <th>Customer</th>
                    <?php endif; ?>
                    <th>Date & Time</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders_list)): ?>
                    <tr>
                        <td colspan="<?= $user->is_admin() ? '6' : '5' ?>" class="empty-table-cell">
                            <i class="fa-solid fa-receipt" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            No orders found yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders_list as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?= str_pad((string)$order['order_id'], 5, '0', STR_PAD_LEFT) ?></strong>
                            </td>
                            <?php if ($user->is_admin()): ?>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($order['user_name'] ?? 'Unknown') ?></strong>
                                        <div style="font-size: 0.8rem; color: var(--palette-gray);"><?= htmlspecialchars($order['user_email'] ?? '') ?></div>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td>
                                <?= date('M d, Y · h:i A', strtotime($order['order_date'])) ?>
                            </td>
                            <td>
                                <strong>₱<?= number_format((float)$order['total_amount'], 2) ?></strong>
                            </td>
                            <td>
                                <?php 
                                $status = strtolower($order['status'] ?? 'pending');
                                $status_class = match($status) {
                                    'completed', 'delivered' => 'badge-status active',
                                    'cancelled', 'declined' => 'badge-status banned',
                                    default => 'badge-status pending'
                                };
                                ?>
                                <span class="<?= $status_class ?>"><?= ucfirst(htmlspecialchars($order['status'] ?? 'Pending')) ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>checkout/summary.php?order_id=<?= (int)$order['order_id'] ?>" class="table-btn-action primary" style="text-decoration: none; display: inline-flex;" title="View Order Summary">
                                    <i class="fa-solid fa-file-invoice"></i> View Summary
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
