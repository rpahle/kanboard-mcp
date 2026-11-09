<div class="page-header">
    <h2><?= t('Delete MCP token') ?></h2>
</div>

<div class="alert alert-error">
    <p><?= t('Any application using this token will stop working immediately. This cannot be undone.') ?></p>
</div>

<table class="table-striped">
    <tr>
        <th><?= t('Name') ?></th>
        <td><?= $this->text->e($token['name'] ?: '#' . $token['id']) ?></td>
    </tr>
    <tr>
        <th><?= t('Created') ?></th>
        <td><?= $this->dt->datetime($token['created_at']) ?></td>
    </tr>
    <tr>
        <th><?= t('Allowed projects') ?></th>
        <td><?= $token['allowed_projects'] === null ? '<strong>' . t('All projects (unrestricted)') . '</strong>' : $this->text->e($token['allowed_projects'] === '' ? t('None') : $token['allowed_projects']) ?></td>
    </tr>
    <tr>
        <th><?= t('Blocked tools') ?></th>
        <td><?= $this->text->e($token['blocked_tools'] === null || $token['blocked_tools'] === '' ? t('None') : $token['blocked_tools']) ?></td>
    </tr>
</table>

<form method="post" autocomplete="off"
      action="<?= $this->url->href('ModelContextProtocolController', 'deleteToken', array('plugin' => 'ModelContextProtocol')) ?>">
    <?= $this->form->csrf() ?>
    <input type="hidden" name="token_id" value="<?= (int) $token['id'] ?>">

    <div class="form-actions">
        <button type="submit" class="btn btn-red"><?= t('Delete token') ?></button>
        <?= t('or') ?>
        <?= $this->url->link(t('cancel'), 'ConfigController', 'integrations') ?>
    </div>
</form>
