<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Html->link(__('New Rbruteforce'), ['action' => 'add']) ?></li>
	</ul>
</div>
<div class="rbruteforces index large-10 medium-9 columns">
	<table cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th><?= $this->Paginator->sort('ip') ?></th>
			<th><?= $this->Paginator->sort('url') ?></th>
			<th><?= $this->Paginator->sort('expire') ?></th>
			<th class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($rbruteforces as $rbruteforce): ?>
		<tr>
			<td><?= h($rbruteforce->ip) ?></td>
			<td><?= h($rbruteforce->url) ?></td>
			<td><?= h($rbruteforce->expire) ?></td>
			<td class="actions">
				<?= $this->Html->link(__('View'), ['action' => 'view', $rbruteforce->expire]) ?>
				<?= $this->Html->link(__('Edit'), ['action' => 'edit', $rbruteforce->expire]) ?>
				<?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $rbruteforce->expire], ['confirm' => __('Are you sure you want to delete # {0}?', $rbruteforce->expire)]) ?>
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
