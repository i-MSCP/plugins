<?php
/**
 * User Interface class for the Tasklist plugin
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2012, Kolab Systems AG <contact@kolabsys.com>
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

    function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->rc = $plugin->rc;
    }

    /**
    * Calendar UI initialization and requests handlers
    */
    public function init()
    {
        if ($this->ready)  // already done
            return;

        // add taskbar button
        $this->plugin->add_button(array(
            'command' => 'tasks',
            'class'   => 'button-tasklist',
            'classsel' => 'button-tasklist button-selected',
            'innerclass' => 'button-inner',
            'label'   => 'tasklist.navtitle',
        ), 'taskbar');

        $this->plugin->include_stylesheet($this->plugin->local_skin_path() . '/tasklist.css');
        $this->plugin->include_script('tasklist_base.js');

        // copy config to client
        // $this->rc->output->set_env('tasklist_settings', $settings);

        $this->ready = true;
  }

    /**
    * Register handler methods for the template engine
    */
    public function init_templates()
    {
        $this->plugin->register_handler('plugin.tasklists', array($this, 'tasklists'));
        $this->plugin->register_handler('plugin.tasklist_select', array($this, 'tasklist_select'));
        $this->plugin->register_handler('plugin.category_select', array($this, 'category_select'));
        $this->plugin->register_handler('plugin.searchform', array($this->rc->output, 'search_form'));
        $this->plugin->register_handler('plugin.quickaddform', array($this, 'quickadd_form'));
        $this->plugin->register_handler('plugin.tasklist_editform', array($this, 'tasklist_editform'));
        $this->plugin->register_handler('plugin.tasks', array($this, 'tasks_resultview'));
        $this->plugin->register_handler('plugin.tagslist', array($this, 'tagslist'));
        $this->plugin->register_handler('plugin.tags_editline', array($this, 'tags_editline'));
        $this->plugin->register_handler('plugin.alarm_select', array($this, 'alarm_select'));
        $this->plugin->register_handler('plugin.attachments_form', array($this, 'attachments_form'));
        $this->plugin->register_handler('plugin.attachments_list', array($this, 'attachments_list'));
        $this->plugin->register_handler('plugin.filedroparea', array($this, 'file_drop_area'));

        $this->plugin->include_script('jquery.tagedit.js');
        $this->plugin->include_script('tasklist.js');
    }

    /**
     *
     */
    function tasklists($attrib = array())
    {
        $lists = $this->plugin->driver->get_lists();

        $li = '';
        foreach ((array)$lists as $id => $prop) {
            if ($attrib['activeonly'] && !$prop['active'])
              continue;

            unset($prop['user_id']);
            $prop['alarms'] = $this->plugin->driver->alarms;
            $prop['undelete'] = $this->plugin->driver->undelete;
            $prop['sortable'] = $this->plugin->driver->sortable;
            $prop['attachments'] = $this->plugin->driver->attachments;
            $jsenv[$id] = $prop;

            $html_id = html_identifier($id);
            $class = 'tasks-'  . asciiwords($id, true);

            if (!$prop['editable'])
                $class .= ' readonly';
            if ($prop['class_name'])
                $class .= ' '.$prop['class_name'];

            $li .= html::tag('li', array('id' => 'rcmlitasklist' . $html_id, 'class' => $class),
                html::tag('input', array('type' => 'checkbox', 'name' => '_list[]', 'value' => $id, 'checked' => $prop['active'])) .
                html::span('handle', '&nbsp;') .
                html::span('listname', $prop['name']));
        }

        $this->rc->output->set_env('tasklists', $jsenv);
        $this->rc->output->add_gui_object('folderlist', $attrib['id']);

        return html::tag('ul', $attrib, $li, html::$common_attrib);
    }


    /**
     * Render a HTML select box for list selection
     */
    function tasklist_select($attrib = array())
    {
        $attrib['name']       = 'list';
        $attrib['is_escaped'] = true;
        $select = new html_select($attrib);

        foreach ((array)$this->plugin->driver->get_lists() as $id => $prop) {
            if ($prop['editable'])
                $select->add($prop['name'], $id);
        }

        return $select->show(null);
    }


    function tasklist_editform($attrib = array())
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
            $this->plugin->driver->tasklist_edit_form($fields)
        );
    }

    /**
     * Render HTML form for alarm configuration
     */
    function alarm_select($attrib = array())
    {
        return $this->plugin->lib->alarm_select($attrib, $this->plugin->driver->alarm_types, $this->plugin->driver->alarm_absolute);
    }

    /**
     *
     */
    function quickadd_form($attrib)
    {
        $attrib += array('action' => $this->rc->url('add'), 'method' => 'post', 'id' => 'quickaddform');

        $input = new html_inputfield(array('name' => 'text', 'id' => 'quickaddinput'));
        $button = html::tag('input', array('type' => 'submit', 'value' => '+', 'class' => 'button mainaction'));

        $this->rc->output->add_gui_object('quickaddform', $attrib['id']);
        return html::tag('form', $attrib, $input->show() . $button);
    }

    /**
     * The result view
     */
    function tasks_resultview($attrib)
    {
        $attrib += array('id' => 'rcmtaskslist');

        $this->rc->output->add_gui_object('resultlist', $attrib['id']);

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

        $this->rc->output->add_gui_object('tagslist', $attrib['id']);
        return html::tag('ul', $attrib, '');
    }

    /**
     * Interactive UI element to add/remove tags
     */
    function tags_editline($attrib)
    {
        $attrib += array('id' => 'rcmtasktagsedit');
        $this->rc->output->add_gui_object('edittagline', $attrib['id']);

        $input = new html_inputfield(array('name' => 'tags[]', 'class' => 'tag', 'size' => $attrib['size'], 'tabindex' => $attrib['tabindex']));
        return html::div($attrib, $input->show(''));
    }

    /**
     * Generate HTML element for attachments list
     */
    function attachments_list($attrib = array())
    {
        if (!$attrib['id'])
            $attrib['id'] = 'rcmtaskattachmentlist';

        $this->rc->output->add_gui_object('attachmentlist', $attrib['id']);

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
        $max_filesize = rcube_upload_init();

        $button = new html_inputfield(array('type' => 'button'));
        $input = new html_inputfield(array(
            'type' => 'file',
            'name' => '_attachments[]',
            'multiple' => 'multiple',
            'size' => $attrib['attachmentfieldsize'],
        ));

        return html::div($attrib,
            html::div(null, $input->show()) .
            html::div('formbuttons', $button->show(rcube_label('upload'), array('class' => 'button mainaction',
                'onclick' => JS_OBJECT_NAME . ".upload_file(this.form)"))) .
            html::div('hint', rcube_label(array('name' => 'maxuploadsize', 'vars' => array('size' => $max_filesize))))
        );
    }

    /**
     * Register UI object for HTML5 drag & drop file upload
     */
    function file_drop_area($attrib = array())
    {
        if ($attrib['id']) {
            $this->rc->output->add_gui_object('filedrop', $attrib['id']);
            $this->rc->output->set_env('filedrop', array('action' => 'upload', 'fieldname' => '_attachments'));
        }
    }

}
