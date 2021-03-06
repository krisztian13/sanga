<div class="sidebar-wrapper">
    <nav class="side-nav">
        <ul>
            <li><?= $this->Html->link(__('New Family'), ['action' => 'add']) ?></li>
        </ul>
    </nav>
</div>
<!-- sidebar wrapper -->
</div>

<div class="content-wrapper">
    <div class="row">
        <div class="families index large-10 medium-9 columns">
            <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($families as $family): ?>
                <tr>
                    <td><?= $this->Number->format($family->id) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $family->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $family->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $family->id], ['confirm' => __('Are you sure you want to delete # {0}?', $family->id)]) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <div class="paginator">
                <ul class="pagination">
                <?php
                    echo $this->Paginator->prev('< ' . __('previous'));
                    echo $this->Paginator->numbers();
                    echo $this->Paginator->next(__('next') . ' >');
                ?>
                </ul>
                <p><?= $this->Paginator->counter() ?></p>
            </div>
        </div>
    </div>
</div>