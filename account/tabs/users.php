<?php
// account tab - manage users (admin only)
// search, filter, ban and unban accounts
?>
<section class="account-panel">
    <div class="panel-header-row">
        <div>
            <h2>Manage Users</h2>
            <p class="subtext">Search customer accounts, view their roles, and manage access.</p>
        </div>
    </div>

    <!-- Search / Filter Bar -->
    <form method="GET" class="tab-search-bar" action="#users">
        <input type="hidden" name="tab" value="users">
        <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="user_q" placeholder="Search by username or email..." value="<?= htmlspecialchars($_GET['user_q'] ?? '') ?>">
        </div>
        <button type="submit" class="account-btn secondary">Search</button>
        <?php if (!empty($_GET['user_q'])): ?>
            <a href="?tab=users#users" class="account-btn outline">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="account-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role / Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admin_users_list)): ?>
                    <tr>
                        <td colspan="4" class="empty-table-cell">No accounts found matching your search.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admin_users_list as $u): ?>
                        <tr>
                            <td>
                                <div class="table-user-cell">
                                    <strong><?= htmlspecialchars($u['user_name']) ?></strong>
                                    <?php if ((int)$u['user_id'] === (int)$user->id): ?>
                                        <span class="badge-mini you">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['user_email']) ?></td>
                            <td>
                                <?php if ($u['user_role'] === 'banned'): ?>
                                    <span class="badge-status banned"><i class="fa-solid fa-ban"></i> Banned</span>
                                <?php elseif ($u['user_role'] === 'admin'): ?>
                                    <span class="badge-status admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                <?php else: ?>
                                    <span class="badge-status active"><i class="fa-solid fa-circle-check"></i> Customer</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ((int)$u['user_id'] !== (int)$user->id): ?>
                                    <?php if ($u['user_role'] === 'banned'): ?>
                                        <!-- Unban form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_user_ban">
                                            <input type="hidden" name="target_user_id" value="<?= (int)$u['user_id'] ?>">
                                            <input type="hidden" name="target_action" value="unban">
                                            <button type="submit" class="table-btn-action success" title="Unban User">
                                                <i class="fa-solid fa-unlock"></i> Unban
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Ban form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_user_ban">
                                            <input type="hidden" name="target_user_id" value="<?= (int)$u['user_id'] ?>">
                                            <input type="hidden" name="target_action" value="ban">
                                            <button type="submit" class="table-btn-action danger" title="Ban User">
                                                <i class="fa-solid fa-ban"></i> Ban
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--palette-gray); italic;">(Self)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
