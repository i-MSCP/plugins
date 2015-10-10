<?php
/**
 * User Interface class for the Tasklist plugin
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2012-2015, Kolab Systems AG <contact@kolabsys.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


class tasklist_ui
{
    private $rc;
    private $plugin;
    private $ready = false;
    private $gui_objects = array();

    function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->rc     = $plugin->rc;
    }

    /**
     * Calendar UI initialization and requests handlers
     */
    public function init()
    {
        if ($this->ready) {
            return;
        }

        // add taskbar button
        $this->plugin->add_button(array(
            'command'    => 'tasks',
            'class'      => 'button-tasklist',
            'classsel'   => 'button-tasklist button-selected',
            'innerclass' => 'button-inner',
            'label'      => 'tasklist.navtitle',
        ), 'taskbar');

        $this->plugin->include_stylesheet($this->plugin->local_skin_path() . '/tasklist.css');

        if ($this->rc->task == 'mail' || $this->rc->task == 'tasks') {
            jqueryui::tagedit();

            $this->plugin->include_script('tasklist_base.js');

            // copy config to client
            $this->rc->output->set_env('tasklist_settings', $this->load_settings());

            // initialize attendees autocompletion
            $this->rc->autocomplete_init();
        }

        $this->ready = true;
    }

    /**
     *
     */
    function load_settings()
    {
        $settings = array();

        $settings['invite_shared'] = (int)$this->rc->config->get('calendar_allow_invite_shared', 0);
        $settings['itip_notify']   = (int)$this->rc->config->get('calendar_itip_send_option', 3);
        $settings['sort_col']      = $this->rc->config->get('tasklist_sort_col', '');
        $settings['sort_order']    = $this->rc->config->get('tasklist_sort_order', 'asc');

        // get user identity to create default attendee
        foreach ($this->rc->user->list_emails() as $rec) {
            if (!$identity)
                $identity = $rec;

            $identity['emails'][] = $rec['email'];
            $settings['identities'][$rec['identity_id']] = $rec['email'];
        }

        $identity['emails'][] = $this->rc->user->get_username();
        $settings['identity'] = array(
            'name'   => $identity['name'],
            'email'  => strtolower($identity['email']),
            'emails' => ';' . strtolower(join(';', $identity['emails']))
        );

        if ($list = rcube_utils::get_input_value('_list', rcube_utils::INPUT_GPC)) {
            $settings['selected_list'] = $list;
        }
        if ($list && ($id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_GPC))) {
            $settings['selected_id'] = $id;

            // check if the referenced task is completed
            $task = $this->plugin->driver->get_task(array('id' => $id, 'list' => $list));
            if ($task && $this->plugin->driver->is_complete($task)) {
                $settings['selected_filter'] = 'complete';
            }
        }
        else if ($filter = rcube_utils::get_input_value('_filter', rcube_utils::INPUT_GPC)) {
            $settings['selected_filter'] = $filter;
        }

        return $settings;
    }

    /**
     * Render a HTML select box for user identity selection
     */
    function identity_select($attrib = array())
    {
        $attrib['name'] = 'identity';
        $select         = new html_select($attrib);
        $identities     = $this->rc->user->list_emails();

        foreach ($identities as $ident) {
            $select->add(format_email_recipient($ident['email'], $ident['name']), $ident['identity_id']);
        }

        return $select->show(null);
    }

    /**
    * Register handler methods for the template engine
    */
    public function init_templates()
    {
        $this->plugin->register_handler('plugin.tasklists', array($this, 'tasklists'));
        $this->plugin->register_handler('plugin.tasklist_select', array($this, 'tasklist_select'));
        $this->plugin->register_handler('plugin.status_select', array($this, 'status_select'));
        $this->plugin->register_handler('plugin.searchform', array($this->rc->output, 'search_form'));
        $this->plugin->register_handler('plugin.quickaddform', array($this, 'quickadd_form'));
        $this->plugin->register_handler('plugin.tasks', array($this, 'tasks_resultview'));
        $this->plugin->register_handler('plugin.tagslist', array($this, 'tagslist'));
        $this->plugin->register_handler('plugin.tags_editline', array($this, 'tags_editline'));
        $this->plugin->register_handler('plugin.alarm_select', array($this, 'alarm_select'));
        $this->plugin->register_handler('plugin.recurrence_form', array($this->plugin->lib, 'recurrence_form'));
        $this->plugin->register_handler('plugin.attachments_form', array($this, 'attachments_form'));
        $this->plugin->register_handler('plugin.attachments_list', array($this, 'attachments_list'));
        $this->plugin->register_handler('plugin.filedroparea', array($this, 'file_drop_area'));
        $this->plugin->register_handler('plugin.attendees_list', array($this, 'attendees_list'));
        $this->plugin->register_handler('plugin.attendees_form', array($this, 'attendees_form'));
        $this->plugin->register_handler('plugin.identity_select', array($this, 'identity_select'));
        $this->plugin->register_handler('plugin.edit_attendees_notify', array($this, 'edit_attendees_notify'));
        $this->plugin->register_handler('plugin.task_rsvp_buttons', array($this->plugin->itip, 'itip_rsvp_buttons'));
        $this->plugin->register_handler('plugin.object_changelog_table', array('libkolab', 'object_changelog_table'));

        jqueryui::tagedit();

        $this->plugin->include_script('tasklist.js');
        $this->rc->output->include_script('treelist.js');

        // include kolab folderlist widget if available
        if (in_array('libkolab', $this->plugin->api->loaded_plugins())) {
            $this->plugin->api->include_script('libkolab/js/folderlist.js');
            $this->plugin->api->include_script('libkolab/js/audittrail.js');
        }
    }

    /**
     *
     */
    public function tasklists($attrib = array())
    {
        $tree = true;
        $jsenv = array();
        $lists = $this->plugin->driver->get_lists($tree);

        // walk folder tree
        if (is_object($tree)) {
            $html = $this->list_tree_html($tree, $lists, $jsenv, $attrib);
        }
        else {
            // fall-back to flat folder listing
            $attrib['class'] .= ' flat';

            $html = '';
            foreach ((array)$lists as $id => $prop) {
                if ($attrib['activeonly'] && !$prop['active'])
                  continue;

                $html .= html::tag('li', array(
                        'id' => 'rcmlitasklist' . rcube_utils::html_identifier($id),
                        'class' => $prop['group'],
                    ),
                    $this->tasklist_list_item($id, $prop, $jsenv, $attrib['activeonly'])
                );
            }
        }

        $this->rc->output->set_env('tasklists', $jsenv);
        $this->register_gui_object('tasklistslist', $attrib['id']);

        return html::tag('ul', $attrib, $html, html::$common_attrib);
    }

    /**
     * Return html for a structured list <ul> for the folder tree
     */
    public function list_tree_html($node, $data, &$jsenv, $attrib)
    {
        $out = '';
        foreach ($node->children as $folder) {
            $id = $folder->id;
            $prop = $data[$id];
            $is_collapsed = false; // TODO: determine this somehow?

            $content = $this->tasklist_list_item($id, $prop, $jsenv, $attrib['activeonly']);

            if (!empty($folder->children)) {
                $content .= html::tag('ul', array('style' => ($is_collapsed ? "display:none;" : null)),
                    $this->list_tree_html($folder, $data, $jsenv, $attrib));
            }

            if (strlen($content)) {
                $out .= html::tag('li', array(
                      'id' => 'rcmlitasklist' . rcube_utils::html_identifier($id),
                      'class' => $prop['group'] . ($prop['virtual'] ? ' virtual' : ''),
                    ),
                    $content);
            }
        }

        return $out;
    }

    /**
     * Helper method to build a tasklist item (HTML content and js data)
     */
    public function tasklist_list_item($id, $prop, &$jsenv, $activeonly = false)
    {
        // enrich list properties with settings from the driver
        if (!$prop['virtual']) {
            unset($prop['user_id']);
            $prop['alarms']      = $this->plugin->driver->alarms;
            $prop['undelete']    = $this->plugin->driver->undelete;
            $prop['sortable']    = $this->plugin->driver->sortable;
            $prop['attachments'] = $this->plugin->driver->attachments;
            $prop['attendees']   = $this->plugin->driver->attendees;
            $prop['caldavurl']   = $this->plugin->driver->tasklist_caldav_url($prop);
            $jsenv[$id] = $prop;
        }

        $classes = array('tasklist');
        $title = $prop['title'] ?: ($prop['name'] != $prop['listname'] || strlen($prop['name']) > 25 ?
          html_entity_decode($prop['name'], ENT_COMPAT, RCUBE_CHARSET) : '');

        if ($prop['virtual'])
            $classes[] = 'virtual';
        else if (!$prop['editable'])
            $classes[] = 'readonly';
        if ($prop['subscribed'])
            $classes[] = 'subscribed';
        if ($prop['class'])
            $classes[] = $prop['class'];

        if (!$activeonly || $prop['active']) {
            $label_id = 'tl:' . $id;
            return html::div(join(' ', $classes),
                html::span(array('class' => 'listname', 'title' => $title, 'id' => $label_id), $prop['listname'] ?: $prop['name']) .
                  ($prop['virtual'] ? '' :
                      html::tag('input', array('type' => 'checkbox', 'name' => '_list[]', 'value' => $id, 'checked' => $prop['active'], 'aria-labelledby' => $label_id)) .
                      html::span('actions', 
                          ($prop['removable'] ? html::a(array('href' => '#', 'class' => 'remove', 'title' => $this->plugin->gettext('removelist')), ' ') : '') .
                          html::a(array('href' => '#', 'class' => 'quickview', 'title' => $this->plugin->gettext('focusview'), 'role' => 'checkbox', 'aria-checked' => 'false'), ' ') .
                          (isset($prop['subscribed']) ? html::a(array('href' => '#', 'class' => 'subscribed', 'title' => $this->plugin->gettext('tasklistsubscribe'), 'role' => 'checkbox', 'aria-checked' => $prop['subscribed'] ? 'true' : 'false'), ' ') : '')
                      )
                )
            );
        }

        return '';
    }

    /**
     * Render HTML form for task status selector
     */
    function status_select($attrib = array())
    {
        $attrib['name'] = 'status';
        $select = new html_select($attrib);
        $select->add('---', '');
        $select->add($this->plugin->gettext('status-needs-action'), 'NEEDS-ACTION');
        $select->add($this->plugin->gettext('status-in-process'),   'IN-PROCESS');
        $select->add($this->plugin->gettext('status-completed'),    'COMPLETED');
        $select->add($this->plugin->gettext('status-cancelled'),    'CANCELLED');

        return $select->show(null);
    }

    /**
     * Render a HTML select box for list selection
     */
    function tasklist_select($attrib = array())
    {
        $attrib['name']       = 'list';
        $attrib['is_escaped'] = true;
        $select = new html_select($attrib);
        $default = null;

        foreach ((array)$this->plugin->driver->get_lists() as $id => $prop) {
            if ($prop['editable'] || strpos($prop['rights'], 'i') !== false) {
                $select->add($prop['name'], $id);
                if (!$default || $prop['default'])
                    $default = $id;
            }
        }

        return $select->show($default);
    }


    function tasklist_editform($action, $list = array())
    {
        $fields = array(
            'name' => array(
                'id' => 'taskedit-tasklistame',
                'label' => $this->plugin->gettext('listname'),
                'value' => html::tag('input', array('id' => 'taskedit-tasklistame', 'name' => 'name', 'type' => 'text', 'class' => 'text', 'size' => 40)),
            ),
/*
            'color' => array(
                'id' => 'taskedit-color',
                'label' => $this->plugin->gettext('color'),
                'value' => html::tag('input', array('id' => 'taskedit-color', 'name' => 'color', 'type' => 'text', 'class' => 'text colorpicker', 'size' => 6)),
            ),
*/
            'showalarms' => array(
                'id' => 'taskedit-showalarms',
                'label' => $this->plugin->gettext('showalarms'),
                'value' => html::tag('input', array('id' => 'taskedit-showalarms', 'name' => 'color', 'type' => 'checkbox')),
            ),
        );

        return html::tag('form', array('action' => "#", 'method' => "post", 'id' => 'tasklisteditform'),
            $this->plugin->driver->tasklist_edit_form($action, $list, $fields)
        );
    }

    /**
     * Render HTML form for alarm configuration
     */
    function alarm_select($attrib = array())
    {
        $attrib['_type'] = 'task';
        return $this->plugin->lib->alarm_select($attrib, $this->plugin->driver->alarm_types, $this->plugin->driver->alarm_absolute);
    }

    /**
     *
     */
    function quickadd_form($attrib)
    {
        $attrib += array('action' => $this->rc->url('add'), 'method' => 'post', 'id' => 'quickaddform');

        $label = html::label(array('for' => 'quickaddinput', 'class' => 'voice'), $this->plugin->gettext('quickaddinput'));
        $input = new html_inputfield(array('name' => 'text', 'id' => 'quickaddinput'));
        $button = html::tag('input', array('type' => 'submit', 'value' => '+', 'title' => $this->plugin->gettext('createtask'), 'class' => 'button mainaction'));

        $this->register_gui_object('quickaddform', $attrib['id']);
        return html::tag('form', $attrib, $label . $input->show() . $button);
    }

    /**
     * The result view
     */
    function tasks_resultview($attrib)
    {
        $attrib += array('id' => 'rcmtaskslist');

        $this->register_gui_object('resultlist', $attrib['id']);

        unset($attrib['name']);
        return html::tag('ul', $attrib, '');
    }

    /**
     * Container for a tags cloud
     */
    function tagslist($attrib)
    {
        $attrib += array('id' => 'rcmtasktagslist');
        unset($attrib['name']);

        $this->register_gui_object('tagslist', $attrib['id']);
        return html::tag('ul', $attrib, '');
    }

    /**
     * Interactive UI element to add/remove tags
     */
    function tags_editline($attrib)
    {
        $attrib += array('id' => 'rcmtasktagsedit');
        $this->register_gui_object('edittagline', $attrib['id']);

        $input = new html_inputfield(array('name' => 'tags[]', 'class' => 'tag', 'size' => $attrib['size'], 'tabindex' => $attrib['tabindex']));
        unset($attrib['tabindex']);
        return html::div($attrib, $input->show(''));
    }

    /**
     * Generate HTML element for attachments list
     */
    function attachments_list($attrib = array())
    {
        if (!$attrib['id'])
            $attrib['id'] = 'rcmtaskattachmentlist';

        $this->register_gui_object('attachmentlist', $attrib['id']);

        return html::tag('ul', $attrib, '', html::$common_attrib);
    }

    /**
     * Generate the form for event attachments upload
     */
    function attachments_form($attrib = array())
    {
        // add ID if not given
        if (!$attrib['id'])
            $attrib['id'] = 'rcmtaskuploadform';

        // Get max filesize, enable upload progress bar
        $max_filesize = $this->rc->upload_init();

        $button = new html_inputfield(array('type' => 'button'));
        $input = new html_inputfield(array(
            'type' => 'file',
            'name' => '_attachments[]',
            'multiple' => 'multiple',
            'size' => $attrib['attachmentfieldsize'],
        ));

        return html::div($attrib,
            html::div(null, $input->show()) .
            html::div('formbuttons', $button->show($this->rc->gettext('upload'), array('class' => 'button mainaction',
                'onclick' => rcmail_output::JS_OBJECT_NAME . ".upload_file(this.form)"))) .
            html::div('hint', $this->rc->gettext(array('name' => 'maxuploadsize', 'vars' => array('size' => $max_filesize))))
        );
    }

    /**
     * Register UI object for HTML5 drag & drop file upload
     */
    function file_drop_area($attrib = array())
    {
        if ($attrib['id']) {
            $this->register_gui_object('filedrop', $attrib['id']);
            $this->rc->output->set_env('filedrop', array('action' => 'upload', 'fieldname' => '_attachments'));
        }
    }

    /**
     *
     */
    function attendees_list($attrib = array())
    {
        // add "noreply" checkbox to attendees table only
        $invitations = strpos($attrib['id'], 'attend') !== false;

        $invite = new html_checkbox(array('value' => 1, 'id' => 'edit-attendees-invite'));
        $table  = new html_table(array('cols' => 4 + intval($invitations), 'border' => 0, 'cellpadding' => 0, 'class' => 'rectable'));

//      $table->add_header('role', $this->plugin->gettext('role'));
        $table->add_header('name', $this->plugin->gettext($attrib['coltitle'] ?: 'attendee'));
        $table->add_header('confirmstate', $this->plugin->gettext('confirmstate'));
        if ($invitations) {
            $table->add_header(array('class' => 'invite', 'title' => $this->plugin->gettext('sendinvitations')),
                $invite->show(1) . html::label('edit-attendees-invite', $this->plugin->gettext('sendinvitations')));
        }
        $table->add_header('options', '');

        // hide invite column if disabled by config
        $itip_notify = (int)$this->rc->config->get('calendar_itip_send_option', 3);
        if ($invitations && !($itip_notify & 2)) {
            $css = sprintf('#%s td.invite, #%s th.invite { display:none !important }', $attrib['id'], $attrib['id']);
            $this->rc->output->add_footer(html::tag('style', array('type' => 'text/css'), $css));
        }

        return $table->show($attrib);
    }

    /**
     *
     */
    function attendees_form($attrib = array())
    {
        $input    = new html_inputfield(array('name' => 'participant', 'id' => 'edit-attendee-name', 'size' => 30));
        $textarea = new html_textarea(array('name' => 'comment', 'id' => 'edit-attendees-comment',
            'rows' => 4, 'cols' => 55, 'title' => $this->plugin->gettext('itipcommenttitle')));

        return html::div($attrib,
            html::div(null, $input->show() . " " .
                html::tag('input', array('type' => 'button', 'class' => 'button', 'id' => 'edit-attendee-add', 'value' => $this->plugin->gettext('addattendee')))
                // . " " . html::tag('input', array('type' => 'button', 'class' => 'button', 'id' => 'edit-attendee-schedule', 'value' => $this->plugin->gettext('scheduletime').'...'))
                ) .
            html::p('attendees-commentbox', html::label(null, $this->plugin->gettext('itipcomment') . $textarea->show()))
        );
    }

    /**
     *
     */
    function edit_attendees_notify($attrib = array())
    {
        $checkbox = new html_checkbox(array('name' => '_notify', 'id' => 'edit-attendees-donotify', 'value' => 1));
        return html::div($attrib, html::label(null, $checkbox->show(1) . ' ' . $this->plugin->gettext('sendnotifications')));
    }

    /**
     * Wrapper for rcube_output_html::add_gui_object()
     */
    function register_gui_object($name, $id)
    {
        $this->gui_objects[$name] = $id;
        $this->rc->output->add_gui_object($name, $id);
    }

    /**
     * Getter for registered gui objects.
     * (for manual registration when loading the inline UI)
     */
    function get_gui_objects()
    {
        return $this->gui_objects;
    }
}
