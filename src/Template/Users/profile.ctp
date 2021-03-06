<?php
print $this->Html->script('sanga.users.profile.js', ['block' => true]);
?>
<div class="row">
    <div class="large-10 medium-9 columns">
    <?php
    print $this->Html->link($this->Html->image('edit.png'),
                   ['action' => 'edit', $user->id],
                   ['id' => 'editlink', 'escape' => false]);

    print $this->element('ajax-images');

    print $this->Form->create($user, ['id'=> 'editForm', 'url' => ['action' => 'edit', $user->id]]);
    ?>
    <div class="user-details-view">
        <div class="main-title row">
            <div class="column large-12">
                <h2><?= h($user->name) ?></h2>
            </div>
        </div><!-- row -->
        <div class="row">
            <!--div class="user-profile-image column large-3">
                <img src="http://cdn.livestream.com/website/ba23e87/assets/thumbnails/profile.png" alt="">
            </div-->
            <div class="user-profile-details column large-6">
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Name') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->name) ?></span>
                            <?php
                            print $this->Form->input('name',
                                               ['templates' => ['inputContainer' => '{{content}}'],
                                                'class' => 'editbox',
                                                'label' => false,
                                                'value' => h($user->name)
                                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Password') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta">******</span>
                            <?php
                            print $this->Form->input('password',
                                               ['templates' => ['inputContainer' => '{{content}}'],
                                                'class' => 'editbox',
                                                'label' => false,
                                                'value' => false
                                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Realname') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->realname) ?></span>
                            <?php
                            print $this->Form->input('realname',
                                               ['templates' => ['inputContainer' => '{{content}}'],
                                                'class' => 'editbox',
                                                'label' => false,
                                                'value' => h($user->realname)
                                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Email') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->email) ?></span>
                            <?php
                            print $this->Form->input('email',
                                               ['templates' => ['inputContainer' => '{{content}}'],
                                                'class' => 'editbox',
                                                'label' => false,
                                                'value' => h($user->email)
                                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Phone') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->phone) ?></span>
                            <?php
                            print $this->Form->input('phone',
                                ['templates' => ['inputContainer' => '{{content}}'],
                                    'class' => 'editbox',
                                    'label' => false,
                                    'value' => h($user->phone)
                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Responsible') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->responsible) ?></span>
                            <?php
                            print $this->Form->input('responsible',
                                ['templates' => ['inputContainer' => '{{content}}'],
                                    'class' => 'editbox',
                                    'label' => false,
                                    'value' => h($user->responsible)
                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Role') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="value"><?= $this->Number->format($user->role) ?></p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Language') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="ed">
                            &nbsp;
                            <span class="dta"><?= h($user->locale) ?></span>
                            <?php
                            print $this->Form->input('locale',
                                               ['templates' => ['inputContainer' => '{{content}}'],
                                                'class' => 'editbox',
                                                'label' => false,
                                                'value' => h($user->locale)
                                                ]);
                            ?>
                        </p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Created') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="value"><?= h($user->created) ?></p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Modified') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="value"><?= h($user->modified) ?></p>
                    </div>
                </div><!-- row -->
                <div class="row">
                    <div class="column large-6 panel">
                        <p class="label"><?= __('Active') ?></p>
                    </div>
                    <div class="column large-6 panel">
                        <p class="value"><?= $user->active ? __('Yes') : __('No'); ?></p>
                    </div>
                </div><!-- row -->
            </div>
            <!-- user profile details -->
        </div><!-- row -->
    </div>
    <!-- user detaisl view -->


    <div class="user-details-view">
        <div class="row">
            <div class="column large-12">
                <h4 class="subheader"><?= __('Events') ?></h4>
                <?php if (!empty($user->events)): ?>
                    <?php
                    foreach ($user->events as $events):
                        print $this->Html->link(h($events->name),
                                        ['controller' => 'Events',
                                         'action' => 'view', $events->id]);
                    endforeach;
                    ?>
                <?php endif; ?>
            </div><!-- column -->
        </div><!-- row -->
    </div>
    <!-- user detaisl view -->

    <div class="user-details-view">
        <div class="row">
            <div class="column large-12">
                <h4 class="subheader"><?= __('Usergroups') ?></h4>
                <?php if (!empty($user->usergroups)): ?>
                    <?php foreach ($user->usergroups as $usergroups): ?>
                            <?= $this->Html->link(h($usergroups->name), ['controller' => 'Usergroups', 'action' => 'view', $usergroups->id]) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div><!-- column -->
        </div><!-- row -->
    </div>
    <!-- user detaisl view -->
    <?php
    print $this->Form->end();
    ?>
    </div>
</div>
<!-- row -->
