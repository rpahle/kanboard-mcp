<div class="panel">
    <h3><i class="fa fa-key fa-fw"></i>&nbsp;<?= t('MCP token created') ?></h3>

    <div class="alert alert-error">
        <p><strong><?= t('Copy it now.') ?></strong> <?= t('This is the only time the token is shown. It cannot be displayed again, so generate a new one if it is lost.') ?></p>
    </div>

    <table class="table-striped">
        <tr>
            <th><?= t('Name') ?></th>
            <td><?= $this->text->e($token_name) ?></td>
        </tr>
        <tr>
            <th><?= t('Allowed projects') ?></th>
            <td><?= $allowed_projects === null ? '<strong>' . t('All projects (unrestricted)') . '</strong>' : $this->text->e($allowed_projects) ?></td>
        </tr>
        <tr>
            <th><?= t('Blocked tools') ?></th>
            <td><?= $blocked_tools === null ? t('None') : $this->text->e($blocked_tools) ?></td>
        </tr>
    </table>

    <div class="form-group">
        <label><strong><?= t('MCP Client Configuration') ?></strong></label>
        <textarea readonly
                  class="form-control"
                  style="background-color: #f5f5f5; font-family: monospace; height: 120px; resize: vertical; width: 100%;"
                  onclick="this.select();">{
  "mcpServers": {
    "kanboard": {
      "url": "<?= $this->text->e($mcp_url) ?>"
    }
  }
}</textarea>
        <p class="form-help"><?= t('Copy this configuration to your MCP client settings (e.g., Claude Desktop app config). The server URL and authentication token are included.') ?></p>
    </div>

    <p><a href="<?= $this->url->href('ConfigController', 'integrations') ?>"><?= t('Back to integrations') ?></a></p>
</div>
