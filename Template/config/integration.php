<?php
// This panel is rendered inside Kanboard's own integrations <form>. It must not contain
// a <form> or any input: a nested form is dropped by the browser, and every field would be
// posted to Kanboard's settings save. The creation form lives on its own page (token_form).
?>
<div class="panel">
    <h3><i class="fa fa-cog fa-fw"></i>&nbsp;<?= t('Model Context Protocol') ?></h3>

    <p><?= t('Control Kanboard via Model Context Protocol.') ?></p>

    <?php $tokens = $this->task->mcpTokenModel->getAll(); ?>

    <?php if (empty($tokens)): ?>
        <p class="alert"><?= t('No MCP token has been generated yet. Generate a token to enable MCP access.') ?></p>
    <?php else: ?>
        <table class="table-striped table-scrolling">
            <tr>
                <th><?= t('Name') ?></th>
                <th><?= t('Status') ?></th>
                <th><?= t('Created') ?></th>
                <th><?= t('Allowed projects') ?></th>
                <th><?= t('Blocked tools') ?></th>
                <th></th>
            </tr>
            <?php foreach ($tokens as $row): ?>
                <tr>
                    <td><?= $this->text->e($row['name'] ?: '#' . $row['id']) ?></td>
                    <td><?= $row['is_active'] ? t('Active') : t('Inactive') ?></td>
                    <td><?= $this->dt->datetime($row['created_at']) ?></td>
                    <td>
                        <?php if ($row['allowed_projects'] === null): ?>
                            <strong><?= t('All projects (unrestricted)') ?></strong>
                        <?php else: ?>
                            <?= $this->text->e($row['allowed_projects'] === '' ? t('None') : $row['allowed_projects']) ?>
                        <?php endif ?>
                    </td>
                    <td><?= $this->text->e($row['blocked_tools'] === null || $row['blocked_tools'] === '' ? t('None') : $row['blocked_tools']) ?></td>
                    <td>
                        <a href="<?= $this->url->href('ModelContextProtocolController', 'confirmDeleteToken', array('plugin' => 'ModelContextProtocol', 'token_id' => $row['id'])) ?>">
                            <i class="fa fa-trash-o fa-fw"></i><?= t('Delete') ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php endif ?>

    <p>
        <a class="btn btn-blue" href="<?= $this->url->href('ModelContextProtocolController', 'newToken', array('plugin' => 'ModelContextProtocol')) ?>">
            <i class="fa fa-plus fa-fw"></i><?= t('Generate a new token') ?>
        </a>
    </p>

    <div class="alert alert-info">
        <p><strong><?= t('Security Note:') ?></strong> <?= t('A token with no allowed projects has full access to your Kanboard instance. A token value is shown once, when it is created, and cannot be shown again.') ?></p>
    </div>
</div>
