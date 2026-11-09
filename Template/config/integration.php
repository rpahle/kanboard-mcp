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
                </tr>
            <?php endforeach ?>
        </table>
    <?php endif ?>

    <h4><?= t('Generate a new token') ?></h4>
    <form method="post" autocomplete="off"
          action="<?= $this->url->href('ModelContextProtocolController', 'generateToken', array('plugin' => 'ModelContextProtocol')) ?>">
        <?= $this->form->csrf() ?>

        <div class="form-group">
            <label for="mcp-token-name"><?= t('Name') ?></label>
            <input type="text" id="mcp-token-name" name="name" maxlength="255" placeholder="<?= t('e.g. Claude, read-only') ?>">
        </div>

        <div class="form-group">
            <label for="mcp-token-projects"><?= t('Allowed project ids') ?></label>
            <input type="text" id="mcp-token-projects" name="allowed_projects" placeholder="11,16,17,18">
            <p class="form-help"><?= t('Comma-separated. Leave empty for a token with access to every project.') ?></p>
        </div>

        <div class="form-group">
            <label for="mcp-token-blocked"><?= t('Blocked tools') ?></label>
            <input type="text" id="mcp-token-blocked" name="blocked_tools" placeholder="delete_,create_project,reorder_columns">
            <p class="form-help"><?= t('Comma-separated tool names. Each entry also blocks every tool whose name starts with it, so "delete_" blocks all delete tools.') ?></p>
        </div>

        <button type="submit" class="btn btn-blue"><?= t('Generate token') ?></button>
    </form>

    <div class="alert alert-info">
        <p><strong><?= t('Security Note:') ?></strong> <?= t('A token with no allowed projects has full access to your Kanboard instance. A token value is shown once, when it is created, and cannot be shown again.') ?></p>
    </div>
</div>
