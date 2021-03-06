<div class="title">
	<div class="action-buttons">
		<?php echo $this->Html->link(__d('tournament', 'View Event'), array('action' => 'view', 'league' => $event['League']['slug'], 'event' => $event['Event']['slug']), array('class' => 'button')); ?>
		<?php echo $this->Html->link(__d('tournament', 'View Brackets'), array('action' => 'bracket', 'league' => $event['League']['slug'], 'event' => $event['Event']['slug']), array('class' => 'button')); ?>
	</div>

	<h2><?php echo $event['Event']['name']; ?> - <?php echo __d('tournament', 'Teams'); ?></h2>
</div>

<div class="container">
	<?php
	echo $this->element('Admin.pagination', array('class' => 'top'));
	echo $this->element('tables/event_teams');
	echo $this->element('Admin.pagination', array('class' => 'bottom')); ?>
</div>