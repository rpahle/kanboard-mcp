<div class="page-header">
    <h2><?= t('Generate a new MCP token') ?></h2>
</div>

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

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Generate token') ?></button>
        <?= t('or') ?>
        <?= $this->url->link(t('cancel'), 'ConfigController', 'integrations') ?>
    </div>
</form>
