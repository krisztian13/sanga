<?php
print $this->Html->script('sanga.autocompleteBuilder.js', ['block' => true]);
print $this->Html->script('sanga.histories.import.js', ['block' => true]);
?>
<div class="sidebar-wrapper">
    <nav class="side-nav">
        <ul>
        <li><?= $this->Html->link(__('Sample import file'), $this->Html->webroot . '/files/histories_csv-import.xlsx') ?></li>
        </ul>
    </nav>
</div>
<!-- sidebar wrapper -->

<div class="content-wrapper">
    <div class="row">
        <h1><?= __('Histories Import') ?></h1>
        <div class="imports index large-10 medium-9 columns">
            <?php
            echo $this->Form->create(null, ['type' => 'file']);
            echo $this->Form->input('file', ['type' => 'file']);
            echo $this->Form->submit();
            echo $this->Form->end();
            ?>
        </div>
    </div>

    <?php if (isset($imported) && $imported) : ?>
        <div class="row">
            <h2><?= __('Imported') ?></h2>
            <div class="imports index large-10 medium-9 columns">
                <p class="message success">
                    <?= __('Imported {0} histories', $imported) ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($notImported) && $notImported) : ?>
        <div class="row">
            <h3><?= __('Errors') ?></h3>
            <div class="imports index large-10 medium-9 columns">
                <p class="message error">
                    <?= __('Not imported {0} histories', $notImported) ?>
                </p>
                <?php
                //debug($fields);
                //debug($errors);
                array_push($fields, __('Save'));
                echo '<table>';
                echo $this->Html->tableHeaders($fields);
                foreach ($errors as $e) {
                    echo '<tr>';
                    foreach ($fields as $field) {
                        if (in_array($field, ['contact', 'group', 'event', 'unit'])) {
                            $field .= '_id';
                        }
                        $tdContent = $tdTitle = $tdClass = '';
                        if (isset($e['data'][$field])) {
                            if (isset($e['errors']) && strpos($e['errors'], $field) !== false)
                            {
                                $tdTitle =  ' title="' . $e['errors'] . ' ' . __('Click to edit') . '"';
                                $tdClass = ' class="message error"';
                                $tdContent = $e['data'][$field];
                            } else{
                                $tdContent = $e['data'][$field];
                            }
                        }
                        echo '<td data-id="' . $field . '"' . $tdTitle . $tdClass . '>';
                        if (is_array($tdContent))
                        {
                            echo implode(',', $tdContent['_ids']);
                        } else {
                            echo $tdContent;
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

