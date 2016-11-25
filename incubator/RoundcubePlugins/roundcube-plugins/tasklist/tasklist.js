/**
 * Client scripts for the Tasklist plugin
 *
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
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
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */
 
function rcube_tasklist_ui(settings)
{
    // extend base class
    rcube_libcalendaring.call(this, settings);

    /*  constants  */
    var FILTER_MASK_ALL = 0;
    var FILTER_MASK_TODAY = 1;
    var FILTER_MASK_TOMORROW = 2;
    var FILTER_MASK_WEEK = 4;
    var FILTER_MASK_LATER = 8;
    var FILTER_MASK_NODATE = 16;
    var FILTER_MASK_OVERDUE = 32;
    var FILTER_MASK_FLAGGED = 64;
    var FILTER_MASK_COMPLETE = 128;
    var FILTER_MASK_ASSIGNED = 256;
    var FILTER_MASK_MYTASKS = 512;

    var filter_masks = {
        all:      FILTER_MASK_ALL,
        today:    FILTER_MASK_TODAY,
        tomorrow: FILTER_MASK_TOMORROW,
        week:     FILTER_MASK_WEEK,
        later:    FILTER_MASK_LATER,
        nodate:   FILTER_MASK_NODATE,
        overdue:  FILTER_MASK_OVERDUE,
        flagged:  FILTER_MASK_FLAGGED,
        complete: FILTER_MASK_COMPLETE,
        assigned: FILTER_MASK_ASSIGNED,
        mytasks:  FILTER_MASK_MYTASKS
    };

    /*  private vars  */
    var tagsfilter = [];
    var filtermask = FILTER_MASK_ALL;
    var loadstate = { filter:-1, lists:'', search:null };
    var idcount = 0;
    var focusview = false;
    var focusview_lists = [];
    var saving_lock;
    var ui_loading;
    var taskcounts = {};
    var listindex = [];
    var listdata = {};
    var tags = [];
    var draghelper;
    var search_request;
    var search_query;
    var completeness_slider;
    var task_draghelper;
    var tag_draghelper;
    var task_drag_active = false;
    var list_scroll_top = 0;
    var scroll_delay = 400;
    var scroll_step = 5;
    var scroll_speed = 20;
    var scroll_sensitivity = 40;
    var scroll_timer;
    var tasklists_widget;
    var focused_task;
    var focused_subclass;
    var task_attendees = [];
    var attendees_list;
    var me = this;

    // general datepicker settings
    var datepicker_settings = {
      // translate from PHP format to datepicker format
      dateFormat: settings['date_format'].replace(/M/g, 'm').replace(/mmmmm/, 'MM').replace(/mmm/, 'M').replace(/dddd/, 'DD').replace(/ddd/, 'D').replace(/yy/g, 'y'),
      firstDay : settings['first_day'],
//      dayNamesMin: settings['days_short'],
//      monthNames: settings['months'],
//      monthNamesShort: settings['months'],
      changeMonth: false,
      showOtherMonths: true,
      selectOtherMonths: true
    };
    var extended_datepicker_settings;

    /*  public members  */
    this.tasklists = rcmail.env.tasklists;
    this.selected_task;
    this.selected_list;

    /*  public methods  */
    this.init = init;
    this.edit_task = task_edit_dialog;
    this.print_tasks = print_tasks;
    this.delete_task = delete_task;
    this.add_childtask = add_childtask;
    this.quicksearch = quicksearch;
    this.reset_search = reset_search;
    this.expand_collapse = expand_collapse;
    this.list_delete = list_delete;
    this.list_remove = list_remove;
    this.list_showurl = list_showurl;
    this.list_edit_dialog = list_edit_dialog;
    this.unlock_saving = unlock_saving;

    /* imports */
    var Q = this.quote_html;
    var text2html = this.text2html;
    var event_date_text = this.event_date_text;
    var parse_datetime = this.parse_datetime;
    var date2unixtime = this.date2unixtime;
    var fromunixtime = this.fromunixtime;
    var render_message_links = this.render_message_links;

    /**
     * initialize the tasks UI
     */
    function init()
    {
        if (rcmail.env.action == 'print' && rcmail.task == 'tasks') {
            data_ready({data: rcmail.env.tasks});
            return;
        }

        // initialize task list selectors
        for (var id in me.tasklists) {
            if (settings.selected_list && me.tasklists[settings.selected_list] && !me.tasklists[settings.selected_list].active) {
                me.tasklists[settings.selected_list].active = true;
                me.selected_list = settings.selected_list;
                $(rcmail.gui_objects.tasklistslist).find("input[value='"+settings.selected_list+"']").prop('checked', true);
            }
            if (me.tasklists[id].editable && (!me.selected_list || me.tasklists[id].default || (me.tasklists[id].active && !me.tasklists[me.selected_list].active))) {
                me.selected_list = id;
            }
        }

        // initialize treelist widget that controls the tasklists list
        var widget_class = window.kolab_folderlist || rcube_treelist_widget;
        tasklists_widget = new widget_class(rcmail.gui_objects.tasklistslist, {
            id_prefix: 'rcmlitasklist',
            selectable: true,
            save_state: true,
            keyboard: false,
            searchbox: '#tasklistsearch',
            search_action: 'tasks/tasklist',
            search_sources: [ 'folders', 'users' ],
            search_title: rcmail.gettext('listsearchresults','tasklist')
        });
        tasklists_widget.addEventListener('select', function(node) {
            var id = $(this).data('id');
            rcmail.enable_command('list-edit', has_permission(me.tasklists[node.id], 'wa'));
            rcmail.enable_command('list-delete', has_permission(me.tasklists[node.id], 'xa'));
            rcmail.enable_command('list-import', has_permission(me.tasklists[node.id], 'i'));
            rcmail.enable_command('list-remove', me.tasklists[node.id] && me.tasklists[node.id].removable);
            rcmail.enable_command('list-showurl', me.tasklists[node.id] && !!me.tasklists[node.id].caldavurl);
            me.selected_list = node.id;
        });
        tasklists_widget.addEventListener('subscribe', function(p) {
            var list;
            if ((list = me.tasklists[p.id])) {
                list.subscribed = p.subscribed || false;
                rcmail.http_post('tasklist', { action:'subscribe', l:{ id:p.id, active:list.active?1:0, permanent:list.subscribed?1:0 } });
            }
        });
        tasklists_widget.addEventListener('remove', function(p) {
            if (me.tasklists[p.id] && me.tasklists[p.id].removable) {
                list_remove(p.id);
            }
        });
        tasklists_widget.addEventListener('insert-item', function(p) {
            var list = p.data;
            if (list && list.id && !list.virtual) {
                me.tasklists[list.id] = list;
                var prop = { id:p.id, active:list.active?1:0 };
                if (list.subscribed) prop.permanent = 1;
                rcmail.http_post('tasklist', { action:'subscribe', l:prop });
                list_tasks();
                $(p.item).data('type', 'tasklist');
            }
        });
        tasklists_widget.addEventListener('search-complete', function(data) {
            if (data.length)
                rcmail.display_message(rcmail.gettext('nrtasklistsfound','tasklist').replace('$nr', data.length), 'voice');
            else
                rcmail.display_message(rcmail.gettext('notasklistsfound','tasklist'), 'info');
        });

        // init (delegate) event handler on tasklist checkboxes
        tasklists_widget.container.on('click', 'input[type=checkbox]', function(e) {
            var list, id = this.value;
            if ((list = me.tasklists[id])) {
                list.active = this.checked;
                fetch_counts();
                if (!this.checked) remove_tasks(id);
                else               list_tasks(null);
                rcmail.http_post('tasklist', { action:'subscribe', l:{ id:id, active:list.active?1:0 } });

                // disable focusview
                if (!this.checked && focusview && $.inArray(id, focusview_lists) >= 0) {
                    set_focusview(null);
                }

                // adjust checked state of original list item
                if (tasklists_widget.is_search()) {
                  tasklists_widget.container.find('input[value="'+id+'"]').prop('checked', this.checked);
                }
            }
            e.stopPropagation();
        })
        .on('keypress', 'input[type=checkbox]', function(e) {
            // select tasklist on <Enter>
            if (e.keyCode == 13) {
                tasklists_widget.select(this.value);
                return rcube_event.cancel(e);
            }
        })
        .find('li:not(.virtual)').data('type', 'tasklist');

        // handler for clicks on quickview buttons
        tasklists_widget.container.on('click', '.quickview', function(e){
            var id = $(this).closest('li').attr('id').replace(/^rcmlitasklist/, '');

            if (tasklists_widget.is_search())
              id = id.replace(/--xsR$/, '');

            if (!rcube_event.is_keyboard(e) && this.blur)
              this.blur();

            set_focusview(id, e.shiftKey || e.metaKey || e.ctrlKey);
            e.stopPropagation();
            return false;
        });

        // register dbl-click handler to open calendar edit dialog
        tasklists_widget.container.on('dblclick', ':not(.virtual) > .tasklist', function(e){
            var id = $(this).closest('li').attr('id').replace(/^rcmlitasklist/, '');

            if (tasklists_widget.is_search())
              id = id.replace(/--xsR$/, '');

            list_edit_dialog(id);
        });

        if (me.selected_list) {
            rcmail.enable_command('addtask', true);
            tasklists_widget.select(me.selected_list);
        }

        // register server callbacks
        rcmail.addEventListener('plugin.data_ready', data_ready);
        rcmail.addEventListener('plugin.update_task', update_taskitem);
        rcmail.addEventListener('plugin.refresh_tasks', function(p) { update_taskitem(p, true); });
        rcmail.addEventListener('plugin.update_counts', update_counts);
        rcmail.addEventListener('plugin.insert_tasklist', insert_list);
        rcmail.addEventListener('plugin.update_tasklist', update_list);
        rcmail.addEventListener('plugin.destroy_tasklist', destroy_list);
        rcmail.addEventListener('plugin.unlock_saving', unlock_saving);
        rcmail.addEventListener('requestrefresh', before_refresh);
        rcmail.addEventListener('plugin.reload_data', function(){
            list_tasks(null, true);
            setTimeout(fetch_counts, 200);
        });

        rcmail.addEventListener('plugin.task_render_changelog', task_render_changelog);
        rcmail.addEventListener('plugin.task_show_diff', task_show_diff);
        rcmail.addEventListener('plugin.task_show_revision', function(data){ task_show_dialog(null, data, true); });
        rcmail.addEventListener('plugin.close_history_dialog', close_history_dialog);

        rcmail.register_command('list-sort', list_set_sort, true);
        rcmail.register_command('list-order', list_set_order, (settings.sort_col || 'auto') != 'auto');
        rcmail.register_command('task-history', task_history_dialog, false);

        $('#taskviewsortmenu .by-' + (settings.sort_col || 'auto')).attr('aria-checked', 'true').addClass('selected');
        $('#taskviewsortmenu .sortorder.' + (settings.sort_order || 'asc')).attr('aria-checked', 'true').addClass('selected');

        // start loading tasks
        fetch_counts();
        list_tasks(settings.selected_filter);

        // register event handlers for UI elements
        $('#taskselector a').click(function(e) {
            if (!$(this).parent().hasClass('inactive')) {
                var selector = this.href.replace(/^.*#/, ''),
                    mask = filter_masks[selector],
                    shift = e.shiftKey || e.ctrlKey || e.metaKey;

                if (!shift)
                    filtermask = mask;  // reset selection on regular clicks
                else if (filtermask & mask)
                    filtermask -= mask;
                else
                    filtermask |= mask;

                list_tasks();
            }
            return false;
        });

        // quick-add a task
        $(rcmail.gui_objects.quickaddform).submit(function(e){
            var tasktext = this.elements.text.value,
                rec = { id:-(++idcount), title:tasktext, readonly:true, mask:0, complete:0 };

            if (tasktext && tasktext.length) {
                save_task({ tempid:rec.id, raw:tasktext, list:me.selected_list }, 'new');
                render_task(rec);

                $('#listmessagebox').hide();
            }

            // clear form
            this.reset();
            return false;
        }).find('input[type=text]').placeholder(rcmail.gettext('createnewtask','tasklist'));

        // click-handler on tags list
        $(rcmail.gui_objects.tagslist).on('click', 'li', function(e){
            var item = e.target.nodeName == 'LI' ? $(e.target) : $(e.target).closest('li'),
                tag = item.data('value');

            if (!tag)
                return false;

            // reset selection on regular clicks
            var index = $.inArray(tag, tagsfilter);
            var shift = e.shiftKey || e.ctrlKey || e.metaKey;

            if (!shift) {
                if (tagsfilter.length > 1)
                    index = -1;

                $('li', rcmail.gui_objects.tagslist).removeClass('selected').attr('aria-checked', 'false');
                tagsfilter = [];
            }

            // add tag to filter
            if (index < 0) {
                item.addClass('selected').attr('aria-checked', 'true');
                tagsfilter.push(tag);
            }
            else if (shift) {
                item.removeClass('selected').attr('aria-checked', 'false');
                var a = tagsfilter.slice(0,index);
                tagsfilter = a.concat(tagsfilter.slice(index+1));
            }

            list_tasks();

            // clear text selection in IE after shift+click
            if (shift && document.selection)
              document.selection.empty();

            e.preventDefault();
            return false;
        })
        .on('keypress', 'li', function(e) {
            if (e.keyCode == 13) {
                $(this).trigger('click', { pointerType:'keyboard' });
            }
        })
        .mousedown(function(e){
            // disable content selection with the mouse
            e.preventDefault();
            return false;
        });

        // click-handler on task list items (delegate)
        $(rcmail.gui_objects.resultlist).on('click', function(e){
            var item = $(e.target);
            var className = e.target.className;

            if (item.hasClass('childtoggle')) {
                item = item.parent().find('.taskhead');
                className = 'childtoggle';
            }
            else if (!item.hasClass('taskhead'))
                item = item.closest('div.taskhead');

            // ignore
            if (!item.length)
                return false;

            var id = item.data('id'),
                li = item.parent(),
                rec = listdata[id];

            switch (className) {
                case 'childtoggle':
                    rec.collapsed = !rec.collapsed;
                    li.children('.childtasks:first').toggle().attr('aria-hidden', rec.collapsed ? 'true' : 'false');
                    $(e.target).toggleClass('collapsed').html(rec.collapsed ? '&#9654;' : '&#9660;');
                    rcmail.http_post('tasks/task', { action:'collapse', t:{ id:rec.id, list:rec.list }, collapsed:rec.collapsed?1:0 });
                    if (e.shiftKey)  // expand/collapse all childs
                        li.children('.childtasks:first .childtoggle.'+(rec.collapsed?'expanded':'collapsed')).click();
                    break;

                case 'complete':
                    if (rcmail.busy)
                        return false;

                    save_task_confirm(rec, 'edit', { _status_before:rec.status + '', status:e.target.checked ? 'COMPLETED' : (rec.complete > 0 ? 'IN-PROCESS' : 'NEEDS-ACTION') });
                    item.toggleClass('complete');
                    return true;

                case 'flagged':
                    if (rcmail.busy)
                        return false;

                    rec.flagged = rec.flagged ? 0 : 1;
                    item.toggleClass('flagged').find('.flagged:first').attr('aria-checked', (rec.flagged ? 'true' : 'false'));
                    save_task(rec, 'edit');
                    break;

                case 'date':
                    if (rcmail.busy)
                        return false;

                    var link = $(e.target).html(''),
                        input = $('<input type="text" size="10" />').appendTo(link).val(rec.date || '')

                    input.datepicker($.extend({
                        onClose: function(dateText, inst) {
                            if (dateText != (rec.date || '')) {
                                save_task_confirm(rec, 'edit', { date:dateText });
                            }
                            input.datepicker('destroy').remove();
                            link.html(dateText || rcmail.gettext('nodate','tasklist'));
                        }
                      }, extended_datepicker_settings)
                    )
                    .datepicker('setDate', rec.date)
                    .datepicker('show');
                    break;

                case 'delete':
                    delete_task(id);
                    break;

                case 'actions':
                    var pos, ref = $(e.target),
                        menu = $('#taskitemmenu');

                    if (menu.is(':visible') && menu.data('refid') == id) {
                        rcmail.command('menu-close', 'taskitemmenu');
                    }
                    else {
                        rcmail.enable_command('task-history', me.tasklists[rec.list] && !!me.tasklists[rec.list].history);
                        rcmail.command('menu-open', { menu: 'taskitemmenu', show: true }, e.target, e);
                        menu.data('refid', id);
                        me.selected_task = rec;
                    }
                    e.bubble = false;
                    break;

                case 'extlink':
                    return true;

                default:
                    if (e.target.nodeName != 'INPUT')
                        task_show_dialog(id);
                    break;
            }

            return false;
        })
        .on('dblclick', '.taskhead, .childtoggle', function(e){
            var id, rec, item = $(e.target);
            if (!item.hasClass('taskhead'))
                item = item.closest('div.taskhead');

            if (!rcmail.busy && item.length && (id = item.data('id')) && (rec = listdata[id])) {
                var list = rec.list && me.tasklists[rec.list] ? me.tasklists[rec.list] : {};
                if (rec.readonly || !list.editable)
                    task_show_dialog(id);
                else
                    task_edit_dialog(id, 'edit');
                clearSelection();
            }
        })
        .on('keydown', '.taskhead', function(e) {
            if (e.target.nodeName == 'INPUT' && e.target.type == 'text')
                return true;

            var inc = 1;
            switch (e.keyCode) {
                case 13:  // Enter
                    $(e.target).trigger('click', { pointerType:'keyboard' });
                    return rcube_event.cancel(e);

                case 38: // Up arrow key
                    inc = -1;
                case 40: // Down arrow key
                    if ($(e.target).hasClass('actions')) {
                        // unfold actions menu
                        $(e.target).trigger('click', { pointerType:'keyboard' });
                        return rcube_event.cancel(e);
                    }

                    // focus next/prev task item
                    var x = 0, target = this, items = $(rcmail.gui_objects.resultlist).find('.taskhead:visible');
                    items.each(function(i, item) {
                        if (item === target) {
                            x = i;
                            return false;
                        }
                    });
                    items.get(x + inc).focus();
                    return rcube_event.cancel(e);

                case 37: // Left arrow key
                case 39: // Right arrow key
                    $(this).parent().children('.childtoggle:visible').first().trigger('click', { pointerType:'keyboard' });
                    break;
            }
        })
        .on('focusin', '.taskhead', function(e){
            if (rcube_event.is_keyboard(e)) {
                var item = $(e.target);
                if (!item.hasClass('taskhead'))
                    item = item.closest('div.taskhead');

                var id = item.data('id');
                if (id && listdata[id]) {
                    focused_task = id;
                    focused_subclass = item.get(0) !== e.target ? e.target.className : null;
                }
            }
        })
        .on('focusout', '.taskhead', function(e){
            var item = $(e.target);
            if (focused_task && item.data('id') == focused_task) {
                focused_task = focused_subclass = null;
            }
        });

        /**
         *
         */
        function task_rsvp(response, delegate)
        {
            if (me.selected_task && me.selected_task.attendees && response) {
                // bring up delegation dialog
                if (response == 'delegated' && !delegate) {
                    rcube_libcalendaring.itip_delegate_dialog(function(data) {
                        $('#reply-comment-task-rsvp').val(data.comment);
                        data.rsvp = data.rsvp ? 1 : '';
                        task_rsvp('delegated', data);
                    });
                    return;
                }

                // update attendee status
                for (var data, i=0; i < me.selected_task.attendees.length; i++) {
                    data = me.selected_task.attendees[i];
                    if (settings.identity.emails.indexOf(';'+String(data.email).toLowerCase()) >= 0) {
                        data.status = response.toUpperCase();

                        if (data.status == 'DELEGATED') {
                              data['delegated-to'] = delegate.to;
                        }
                        else {
                            delete data.rsvp;  // unset RSVP flag

                            if (data['delegated-to']) {
                              delete data['delegated-to'];
                              if (data.role == 'NON-PARTICIPANT' && data.status != 'DECLINED') {
                                  data.role = 'REQ-PARTICIPANT';
                              }
                            }
                        }
                    }
                }

                // submit status change to server
                saving_lock = rcmail.set_busy(true, 'tasklist.savingdata');
                rcmail.http_post('tasks/task', {
                    action: 'rsvp',
                    t: $.extend({}, me.selected_task, (delegate || {})),
                    filter: filtermask,
                    status: response,
                    noreply: $('#noreply-task-rsvp:checked').length ? 1 : 0,
                    comment: $('#reply-comment-task-rsvp').val()
                });

                task_show_dialog(me.selected_task.id);
            }
        }

        // init RSVP widget
        $('#task-rsvp input.button').click(function(e) {
            task_rsvp($(this).attr('rel'))
        });

        // register click handler for message links
        $('#task-links, #taskedit-links').on('click', 'li a.messagelink', function(e) {
            rcmail.open_window(this.href);
            return false;
        });

        // register click handler for message delete buttons
        $('#taskedit-links').on('click', 'li a.delete', function(e) {
            remove_link(e.target);
            return false;
        });

        // extended datepicker settings
        var extended_datepicker_settings = $.extend({
            showButtonPanel: true,
            beforeShow: function(input, inst) {
                setTimeout(function(){
                    $(input).datepicker('widget').find('button.ui-datepicker-close')
                        .html(rcmail.gettext('nodate','tasklist'))
                        .attr('onclick', '')
                        .unbind('click')
                        .bind('click', function(e){
                            $(input).datepicker('setDate', null).datepicker('hide');
                        });
                }, 1);
            }
        }, datepicker_settings);
    }

    /**
     * initialize task edit form elements
     */
    function init_taskedit()
    {
        $('#taskedit').tabs({
            activate: function(event, ui) {
                // reset autocompletion on tab change (#3389)
                if (ui.oldPanel.selector == '#taskedit-panel-attendees') {
                    rcmail.ksearch_blur();
                }
            }
        });

        var completeness_slider_change = function(e, ui){
          var v = completeness_slider.slider('value');
          if (v >= 98) v = 100;
          if (v <= 2)  v = 0;
          $('#taskedit-completeness').val(v);
        };
        completeness_slider = $('#taskedit-completeness-slider').slider({
            range: 'min',
            animate: 'fast',
            slide: completeness_slider_change,
            change: completeness_slider_change
        });
        $('#taskedit-completeness').change(function(e){
            completeness_slider.slider('value', parseInt(this.value))
        });

        // register events on alarms and recurrence fields
        me.init_alarms_edit('#taskedit-alarms');
        me.init_recurrence_edit('#eventedit');

        $('#taskedit-date, #taskedit-startdate').datepicker(datepicker_settings);

        $('a.edit-nodate').click(function(){
            var sel = $(this).attr('rel');
            if (sel) $(sel).val('');
            return false;
        });

      // init attendees autocompletion
      var ac_props;
      // parallel autocompletion
      if (rcmail.env.autocomplete_threads > 0) {
        ac_props = {
          threads: rcmail.env.autocomplete_threads,
          sources: rcmail.env.autocomplete_sources
        };
      }
      rcmail.init_address_input_events($('#edit-attendee-name'), ac_props);
      rcmail.addEventListener('autocomplete_insert', function(e) {
          var success = false;
          if (e.field.name == 'participant') {
              success = add_attendees(e.insert, { role:'REQ-PARTICIPANT', status:'NEEDS-ACTION', cutype:(e.data && e.data.type == 'group' ? 'GROUP' : 'INDIVIDUAL') });
          }
          if (e.field && success) {
              e.field.value = '';
          }
      });

      $('#edit-attendee-add').click(function() {
          var input = $('#edit-attendee-name');
          rcmail.ksearch_blur();
          if (add_attendees(input.val(), { role:'REQ-PARTICIPANT', status:'NEEDS-ACTION', cutype:'INDIVIDUAL' })) {
              input.val('');
          }
      });

      // handle change of "send invitations" checkbox
      $('#edit-attendees-invite').change(function() {
        $('#edit-attendees-donotify,input.edit-attendee-reply').prop('checked', this.checked);
        // hide/show comment field
        $('#taskeditform .attendees-commentbox')[this.checked ? 'show' : 'hide']();
      });

      // delegate change task to "send invitations" checkbox
      $('#edit-attendees-donotify').change(function() {
        $('#edit-attendees-invite').click();
        return false;
      });
    }

    /**
     * Check permissions on the given list object
     */
    function has_permission(list, perm)
    {
        // multiple chars means "either of"
        if (String(perm).length > 1) {
            for (var i=0; i < perm.length; i++) {
                if (has_permission(list, perm[i])) {
                    return true;
                }
            }
        }

        if (list.rights && String(list.rights).indexOf(perm) >= 0) {
            return true;
        }

        return (perm == 'i' && list.editable);
    }

    /**
     * Request counts from the server
     */
    function fetch_counts()
    {
        var active = active_lists();
        if (active.length)
            rcmail.http_request('counts', { lists:active.join(',') });
        else
            update_counts({});
    }

    /**
     * List tasks matching the given selector
     */
    function list_tasks(sel, force)
    {
        if (rcmail.busy)
            return;

        if (sel && filter_masks[sel] !== undefined) {
            filtermask = filter_masks[sel];
        }

        var active = active_lists(),
            basefilter = filtermask & FILTER_MASK_COMPLETE ? FILTER_MASK_COMPLETE : FILTER_MASK_ALL,
            reload = force || active.join(',') != loadstate.lists || basefilter != loadstate.filter || loadstate.search != search_query;

        if (active.length && reload) {
            ui_loading = rcmail.set_busy(true, 'loading');
            rcmail.http_request('fetch', { filter:basefilter, lists:active.join(','), q:search_query }, true);
        }
        else if (reload)
            data_ready({ data:[], lists:'', filter:basefilter, search:search_query });
        else
            render_tasklist();

        $('#taskselector li.selected').removeClass('selected').attr('aria-checked', 'false');

        // select all active selectors
        if (filtermask > 0) {
            $.each(filter_masks, function(sel, mask) {
                if (filtermask & mask)
                    $('#taskselector li.'+sel).addClass('selected').attr('aria-checked', 'true');
            });
        }
        else
            $('#taskselector li.all').addClass('selected').attr('aria-checked', 'true');
    }

    /**
     * Remove all tasks of the given list from the UI
     */
    function remove_tasks(list_id)
    {
        // remove all tasks of the given list from index
        var newindex = $.grep(listindex, function(id, i){
            return listdata[id] && listdata[id].list != list_id;
        });

        listindex = newindex;
        render_tasklist();

        // avoid reloading
        me.tasklists[list_id].active = false;
        loadstate.lists = active_lists();
    }

    /**
     * Modify query parameters for refresh requests
     */
    function before_refresh(query)
    {
        query.filter = filtermask == FILTER_MASK_COMPLETE ? FILTER_MASK_COMPLETE : FILTER_MASK_ALL;
        query.lists = active_lists().join(',');
        if (search_query)
            query.q = search_query;

        return query;
    }

    /**
     * Callback if task data from server is ready
     */
    function data_ready(response)
    {
        listdata = {};
        listindex = [];
        loadstate.lists = response.lists;
        loadstate.filter = response.filter;
        loadstate.search = response.search;

        for (var id, i=0; i < response.data.length; i++) {
            id = response.data[i].id;
            listindex.push(id);
            listdata[id] = response.data[i];
            listdata[id].children = [];
            // register a forward-pointer to child tasks
            if (listdata[id].parent_id && listdata[listdata[id].parent_id])
                listdata[listdata[id].parent_id].children.push(id);
        }

        // sort index before rendering
        listindex.sort(function(a, b) { return task_cmp(listdata[a], listdata[b]); });

        append_tags(response.tags || []);
        render_tasklist();

        // show selected task dialog
        if (settings.selected_id) {
            if (listdata[settings.selected_id]) {
                task_show_dialog(settings.selected_id);
                delete settings.selected_id;
            }

            // remove _id from window location
            if (window.history.replaceState) {
                window.history.replaceState({}, document.title, rcmail.url('', { _list: me.selected_list }));
            }
        }

        rcmail.set_busy(false, 'loading', ui_loading);
    }

    /**
     *
     */
    function render_tasklist()
    {
        // clear display
        var id, rec,
            count = 0,
            cache = {},
            activetags = {},
            msgbox = $('#listmessagebox').hide(),
            list = $(rcmail.gui_objects.resultlist).html('');

        for (var i=0; i < listindex.length; i++) {
            id = listindex[i];
            rec = listdata[id];
            if (match_filter(rec, cache)) {
                if (rcmail.env.action == 'print') {
                    render_task_printmode(rec);
                    continue;
                }

                render_task(rec);
                count++;

                // keep a list of tags from all visible tasks
                for (var t, j=0; rec.tags && j < rec.tags.length; j++) {
                    t = rec.tags[j];
                    if (typeof activetags[t] == 'undefined')
                        activetags[t] = 0;
                    activetags[t]++;
                }
            }
        }

        if (rcmail.env.action == 'print')
            return;

        fix_tree_toggles();
        update_tagcloud(activetags);

        if (!count) {
            msgbox.html(rcmail.gettext('notasksfound','tasklist')).show();
            rcmail.display_message(rcmail.gettext('notasksfound','tasklist'), 'voice');
        }
    }

    /**
     * Show/hide child toggle buttons on all visible task items
     */
    function fix_tree_toggles()
    {
        $('.taskitem', rcmail.gui_objects.resultlist).each(function(i,elem){
            var li = $(elem),
                rec = listdata[li.attr('rel')],
                childs = $('.childtasks li', li);

            $('.childtoggle', li)[(childs.length ? 'show' : 'hide')]();
        })
    }

    /**
     * Expand/collapse all task items with childs
     */
    function expand_collapse(expand)
    {
        var collapsed = !expand;

        $('.taskitem .childtasks')[(collapsed ? 'hide' : 'show')]();
        $('.taskitem .childtoggle')
            .removeClass(collapsed ? 'expanded' : 'collapsed')
            .addClass(collapsed ? 'collapsed' : 'expanded')
            .html(collapsed ? '&#9654;' : '&#9660;');

        // store new toggle collapse states
        var ids = [];
        for (var id in listdata) {
            if (listdata[id].children && listdata[id].children.length)
                ids.push(id);
        }
        if (ids.length) {
            rcmail.http_post('tasks/task', { action:'collapse', t:{ id:ids.join(',') }, collapsed:collapsed?1:0 });
        }
    }

    /**
     *
     */
    function append_tags(taglist)
    {
        // find new tags
        var newtags = [];
        for (var i=0; i < taglist.length; i++) {
            if ($.inArray(taglist[i], tags) < 0)
                newtags.push(taglist[i]);
        }
        tags = tags.concat(newtags);

        // append new tags to tag cloud
        $.each(newtags, function(i, tag){
            $('<li role="checkbox" aria-checked="false" tabindex="0"></li>')
                .attr('rel', tag)
                .data('value', tag)
                .html(Q(tag) + '<span class="count"></span>')
                .appendTo(rcmail.gui_objects.tagslist)
                .draggable({
                    addClasses: false,
                    revert: 'invalid',
                    revertDuration: 300,
                    helper: tag_draggable_helper,
                    start: tag_draggable_start,
                    appendTo: 'body',
                    cursor: 'pointer'
                });
            });

        // re-sort tags list
        $(rcmail.gui_objects.tagslist).children('li').sortElements(function(a,b){
            return $.text([a]).toLowerCase() > $.text([b]).toLowerCase() ? 1 : -1;
        });
    }

    /**
     * Display the given counts to each tag and set those inactive which don't
     * have any matching tasks in the current view.
     */
    function update_tagcloud(counts)
    {
        // compute counts first by iterating over all visible task items
        if (typeof counts == 'undefined') {
            counts = {};
            $('li.taskitem', rcmail.gui_objects.resultlist).each(function(i,li){
                var t, id = $(li).attr('rel'),
                    rec = listdata[id];
                for (var j=0; rec && rec.tags && j < rec.tags.length; j++) {
                    t = rec.tags[j];
                    if (typeof counts[t] == 'undefined')
                        counts[t] = 0;
                    counts[t]++;
                }
            });
        }

        $(rcmail.gui_objects.tagslist).children('li').each(function(i,li){
            var elem = $(li), tag = elem.attr('rel'),
                count = counts[tag] || 0;

            elem.children('.count').html(count+'');
            if (count == 0) elem.addClass('inactive');
            else            elem.removeClass('inactive');
        });
    }

    /*  Helper functions for drag & drop functionality of tags  */
    
    function tag_draggable_helper()
    {
        if (!tag_draghelper)
            tag_draghelper = $('<div class="tag-draghelper"></div>');
        else
            tag_draghelper.html('');

        $(this).clone().addClass('tag').appendTo(tag_draghelper);
        return tag_draghelper;
    }

    function tag_draggable_start(event, ui)
    {
        $('.taskhead').droppable({
            hoverClass: 'droptarget',
            accept: tag_droppable_accept,
            drop: tag_draggable_dropped,
            addClasses: false
        });
    }

    function tag_droppable_accept(draggable)
    {
        if (rcmail.busy)
            return false;

        var tag = draggable.data('value'),
            drop_id = $(this).data('id'),
            drop_rec = listdata[drop_id],
            list = drop_rec && me.tasklists[drop_rec.list] ? me.tasklists[drop_rec.list] : { editable:true };

        // target is not editable or already has this tag assigned
        if (!drop_rec || drop_rec.readonly || !has_permission(list, 'i') || (drop_rec.tags && $.inArray(tag, drop_rec.tags) >= 0)) {
            return false;
        }

        return true;
    }

    function tag_draggable_dropped(event, ui)
    {
        var drop_id = $(this).data('id'),
            tag = ui.draggable.data('value'),
            rec = listdata[drop_id];

        if (rec && rec.id) {
            if (!rec.tags) rec.tags = [];
            rec.tags.push(tag);
            save_task(rec, 'edit');
        }
    }

    /**
     *
     */
    function update_counts(counts)
    {
        // got new data
        if (counts)
            taskcounts = counts;

        // iterate over all selector links and update counts
        $('#taskselector a').each(function(i, elem){
            var link = $(elem),
                f = link.parent().attr('class').replace(/\s\w+/, '');
            if (f != 'all')
                link.children('span').html(taskcounts[f] || '')[(taskcounts[f] ? 'show' : 'hide')]();
        });

        // spacial case: overdue
        $('#taskselector li.overdue')[(taskcounts.overdue ? 'removeClass' : 'addClass')]('inactive');
    }

    /**
     * Callback from server to update a single task item
     */
    function update_taskitem(rec, filter)
    {
        // handle a list of task records
        if ($.isArray(rec)) {
            $.each(rec, function(i,r){ update_taskitem(r, filter); });
            return;
        }

        var id = rec.id,
            oldid = rec.tempid || id,
            oldrec = listdata[oldid],
            oldindex = $.inArray(oldid, listindex),
            oldparent = oldrec ? (oldrec._old_parent_id || oldrec.parent_id) : null,
            list = me.tasklists[rec.list];

        if (oldindex >= 0)
            listindex[oldindex] = id;
        else
            listindex.push(id);

        listdata[id] = rec;

        // remove child-pointer from old parent
        if (oldparent && listdata[oldparent] && oldparent != rec.parent_id) {
            var oldchilds = listdata[oldparent].children,
                i = $.inArray(oldid, oldchilds);
            if (i >= 0) {
                listdata[oldparent].children = oldchilds.slice(0,i).concat(oldchilds.slice(i+1));
            }
        }

        // register a forward-pointer to child tasks
        if (rec.parent_id && listdata[rec.parent_id] && listdata[rec.parent_id].children && $.inArray(id, listdata[rec.parent_id].children) < 0)
            listdata[rec.parent_id].children.push(id);

        // restore pointers to my children
        if (!listdata[id].children) {
            listdata[id].children = [];
            for (var pid in listdata) {
                if (listdata[pid].parent_id == id)
                    listdata[id].children.push(pid);
            }
        }

        // copy _depth property from old rec or derive from parent
        if (rec.parent_id && listdata[rec.parent_id]) {
            rec._depth = (listdata[rec.parent_id]._depth || 0) + 1;
        }
        else if (oldrec) {
            rec._depth = oldrec._depth || 0;
        }

        if (list.active || rec.tempid) {
            if (!filter || match_filter(rec, {}))
                render_task(rec, oldid);
        }
        else {
            $('li[rel="'+id+'"]', rcmail.gui_objects.resultlist).remove();
        }

        append_tags(rec.tags || []);
        update_tagcloud();
        fix_tree_toggles();

        // refresh currently displayed task details dialog
        if ($('#taskshow').is(':visible') && me.selected_task && me.selected_task.id == rec.id) {
            task_show_dialog(rec.id);
        }
    }

    /**
     * Submit the given (changed) task record to the server
     */
    function save_task(rec, action)
    {
        // show confirmation dialog when status of an assigned task has changed
        if (rec._status_before !== undefined && is_attendee(rec))
            return save_task_confirm(rec, action);

        if (!rcmail.busy) {
            saving_lock = rcmail.set_busy(true, 'tasklist.savingdata');
            rcmail.http_post('tasks/task', { action:action, t:rec, filter:filtermask });
            $('button.ui-button:ui-button').button('option', 'disabled', rcmail.busy);
            return true;
        }
        
        return false;
    }

    /**
     * Display confirm dialog when modifying/deleting a task record
     */
    var save_task_confirm = function(rec, action, updates)
    {
        var data = $.extend({}, rec, updates || {}),
          notify = false, partstat = false, html = '',
          do_confirm = settings.itip_notify & 2;

        // task has attendees, ask whether to notify them
        if (has_attendees(rec) && is_organizer(rec)) {
            notify = true;
            if (do_confirm) {
                html = rcmail.gettext('changeconfirmnotifications', 'tasklist');
            }
            else {
                data._notify = settings.itip_notify;
            }
        }
        // ask whether to change my partstat and notify organizer
        else if (data._status_before !== undefined && data.status && data._status_before != data.status && is_attendee(rec)) {
          partstat = true;
          if (do_confirm) {
              html = rcmail.gettext('partstatupdatenotification', 'tasklist');
          }
          else if (settings.itip_notify & 1) {
              data._reportpartstat = data.status == 'CANCELLED' ? 'DECLINED' : data.status;
          }
        }

        // remove to avoid endless recursion
        delete data._status_before;

        // show dialog
        if (html) {
            var $dialog = $('<div>').html(html);

            var buttons = [];
            buttons.push({
                text: rcmail.gettext('saveandnotify', 'tasklist'),
                click: function() {
                    if (notify)   data._notify = 1;
                    if (partstat) data._reportpartstat = data.status == 'CANCELLED' ? 'DECLINED' : data.status;
                    save_task(data, action);
                    $(this).dialog('close');
                }
            });
            buttons.push({
                text: rcmail.gettext('save', 'tasklist'),
                click: function() {
                    save_task(data, action);
                    $(this).dialog('close');
                }
            });
            buttons.push({
                text: rcmail.gettext('cancel', 'tasklist'),
                click: function() {
                    $(this).dialog('close');
                    if (updates)
                      render_task(rec, rec.id);  // restore previous state
                }
            });

            $dialog.dialog({
                modal: true,
                width: 460,
                closeOnEscapeType: false,
                dialogClass: 'warning no-close',
                title: rcmail.gettext('changetaskconfirm', 'tasklist'),
                buttons: buttons,
                open: function() {
                    setTimeout(function(){
                      $dialog.parent().find('.ui-button:not(.ui-dialog-titlebar-close)').first().focus();
                    }, 5);
                },
                close: function(){
                    $dialog.dialog('destroy').remove();
                }
            }).addClass('task-update-confirm').show();

            return true;
        }

        // do update
        return save_task(data, action);
    }

    /**
     * Remove saving lock and free the UI for new input
     */
    function unlock_saving()
    {
        if (saving_lock) {
            rcmail.set_busy(false, null, saving_lock);
            $('button.ui-button:ui-button').button('option', 'disabled', false);
        }
    }

    /**
     * Render the given task into the tasks list
     */
    function render_task(rec, replace)
    {
        var tags_html = '';
        for (var j=0; rec.tags && j < rec.tags.length; j++)
            tags_html += '<span class="tag">' + Q(rec.tags[j]) + '</span>';

        var label_id = rcmail.html_identifier(rec.id) + '-title';
        var div = $('<div>').addClass('taskhead').html(
            '<div class="progressbar"><div class="progressvalue" style="width:' + (rec.complete * 100) + '%"></div></div>' +
            '<input type="checkbox" name="completed[]" value="1" class="complete" aria-label="' + rcmail.gettext('complete','tasklist') + '" ' + (is_complete(rec) ? 'checked="checked" ' : '') + '/>' + 
            '<span class="flagged" role="checkbox" tabindex="0" aria-checked="' + (rec.flagged ? 'true' : 'false') + '" aria-label="' + rcmail.gettext('flagged','tasklist') + '"></span>' +
            '<span class="title" id="' + label_id + '">' + text2html(Q(rec.title)) + '</span>' +
            '<span class="tags">' + tags_html + '</span>' +
            '<span class="date">' + Q(rec.date || rcmail.gettext('nodate','tasklist')) + '</span>' +
            '<a href="#" class="actions" aria-haspopup="true" aria-expanded="false">' + rcmail.gettext('taskactions','tasklist') + '</a>'
            )
            .attr('tabindex', '0')
            .attr('aria-labelledby', label_id)
            .data('id', rec.id)
            .draggable({
                revert: 'invalid',
                addClasses: false,
                cursorAt: { left:-10, top:12 },
                helper: task_draggable_helper,
                appendTo: 'body',
                start: task_draggable_start,
                stop: task_draggable_stop,
                drag: task_draggable_move,
                revertDuration: 300
            });

        if (is_complete(rec))
            div.addClass('complete');
        if (rec.flagged)
            div.addClass('flagged');
        if (!rec.date)
            div.addClass('nodate');
        if ((rec.mask & FILTER_MASK_OVERDUE))
            div.addClass('overdue');

        var li, inplace = false, parent = rec.parent_id ? $('li[rel="'+rec.parent_id+'"] > ul.childtasks', rcmail.gui_objects.resultlist) : null;
        if (replace && (li = $('li[rel="'+replace+'"]', rcmail.gui_objects.resultlist)) && li.length) {
            li.children('div.taskhead').first().replaceWith(div);
            li.attr('rel', rec.id);
            inplace = true;
        }
        else {
            li = $('<li role="treeitem">')
                .attr('rel', rec.id)
                .addClass('taskitem')
                .append((rec.collapsed ? '<span class="childtoggle collapsed">&#9654;' : '<span class="childtoggle expanded">&#9660;') + '</span>')
                .append(div)
                .append('<ul class="childtasks" role="group" style="' + (rec.collapsed ? 'display:none' : '') + '" aria-hidden="' + (rec.collapsed ? 'true' : 'false') +'"></ul>');

            if (!parent || !parent.length)
                li.appendTo(rcmail.gui_objects.resultlist);
        }

        if (!inplace && parent && parent.length)
            li.appendTo(parent);

        if (replace) {
            resort_task(rec, li, true);
            // TODO: remove the item after a while if it doesn't match the current filter anymore
        }

        // re-set focus to taskhead element after DOM update
        if (focused_task == rec.id) {
            focus_task(li);
        }
    }

    /**
     * Render the given task into the tasks list (in print mode)
     */
    function render_task_printmode(rec)
    {
        var label_id = rcmail.html_identifier(rec.id) + '-title',
            div = $('<div>').addClass('taskhead')
                .append($('<span class="title">').attr('id', label_id).text(rec.title)),
            parent = rec.parent_id ? $('li[rel="'+rec.parent_id+'"] > ul.childtasks', rcmail.gui_objects.resultlist) : null,
            li = $('<li role="treeitem">').attr('rel', rec.id).addClass('taskitem')
                .append(div)
                .append('<ul class="childtasks" role="group"></ul>');

        if (rec.description)
            div.append($('<span class="description">').text(rec.description));
/*
        if (is_complete(rec))
            div.addClass('complete');
        if (rec.flagged)
            div.addClass('flagged');
        if (!rec.date)
            div.addClass('nodate');
        if (rec.mask & FILTER_MASK_OVERDUE)
            div.addClass('overdue');
*/
        if (!parent || !parent.length)
            li.appendTo(rcmail.gui_objects.resultlist);
        else
            li.appendTo(parent);
    }

    /**
     * Move the given task item to the right place in the list
     */
    function resort_task(rec, li, animated)
    {
        var dir = 0, index, slice, cmp, next_li, next_id, next_rec, insert_after, past_myself;

        // animated moving
        var insert_animated = function(li, before, after) {
            if (before && li.next().get(0) == before.get(0))
                return; // nothing to do
            else if (after && li.prev().get(0) == after.get(0))
                return; // nothing to do
            
            var speed = 300;
            li.slideUp(speed, function(){
                if (before)     li.insertBefore(before);
                else if (after) li.insertAfter(after);
                li.slideDown(speed, function(){
                    if (focused_task == rec.id) {
                        focus_task(li);
                    }
                });
            });
        }

        // remove from list index
        var oldlist = listindex.join('%%%');
        var oldindex = $.inArray(rec.id, listindex);
        if (oldindex >= 0) {
            slice = listindex.slice(0,oldindex);
            listindex = slice.concat(listindex.slice(oldindex+1));
        }

        // find the right place to insert the task item
        li.parent().children('.taskitem').each(function(i, elem){
            next_li = $(elem);
            next_id = next_li.attr('rel');
            next_rec = listdata[next_id];

            if (next_id == rec.id) {
                past_myself = true;
                return 1; // continue
            }

            cmp = next_rec ? task_cmp(rec, next_rec) : 0;

            if (cmp > 0 || (cmp == 0 && !past_myself)) {
                insert_after = next_li;
                return 1; // continue;
            }
            else if (next_li && cmp < 0) {
                if (animated) insert_animated(li, next_li);
                else          li.insertBefore(next_li);
                index = $.inArray(next_id, listindex);
                return false; // break
            }
        });

        if (insert_after) {
            if (animated) insert_animated(li, null, insert_after);
            else          li.insertAfter(insert_after);

            next_id = insert_after.attr('rel');
            index = $.inArray(next_id, listindex);
        }

        // insert into list index
        if (next_id && index >= 0) {
            slice = listindex.slice(0,index);
            slice.push(rec.id);
            listindex = slice.concat(listindex.slice(index));
        }
        else {  // restore old list index
            listindex = oldlist.split('%%%');
        }
    }

    /**
     * Compare function of two task records.
     * (used for sorting)
     */
    function task_cmp(a, b)
    {
        // sort by hierarchy level first
        if ((a._depth || 0) != (b._depth || 0))
            return a._depth - b._depth;

        var p, alt, inv = 1, c = is_complete(a) - is_complete(b), d = c;

        // completed tasks always move to the end
        if (c != 0)
            return c;

        // custom sorting
        if (settings.sort_col && settings.sort_col != 'auto') {
            alt = settings.sort_col == 'datetime' || settings.sort_col == 'startdatetime' ? 99999999999 : 0
            d = (a[settings.sort_col]||alt) - (b[settings.sort_col]||alt);
            inv = settings.sort_order == 'desc' ? -1 : 1;
        }
        // default sorting (auto)
        else {
            if (!d) d = (b._hasdate-0) - (a._hasdate-0);
            if (!d) d = (a.datetime||99999999999) - (b.datetime||99999999999);
        }

        // fall-back to created/changed date
        if (!d) d = (a.created||0) - (b.created||0);
        if (!d) d = (a.changed||0) - (b.changed||0);

        return d * inv;
    }

    /**
     * Set focus on the given task item after DOM update
     */
    function focus_task(li)
    {
        var selector = '.taskhead';
        if (focused_subclass)
            selector += ' .' + focused_subclass
        li.find(selector).focus();
    }

    /**
     * Determine whether the given task should be displayed as "complete"
     */
    function is_complete(rec)
    {
        return ((rec.complete == 1.0 && !rec.status) || rec.status === 'COMPLETED') ? 1 : 0;
    }

    /**
     *
     */
    function get_all_childs(id)
    {
        var cid, childs = [];
        for (var i=0; listdata[id].children && i < listdata[id].children.length; i++) {
            cid = listdata[id].children[i];
            childs.push(cid);
            childs = childs.concat(get_all_childs(cid));
        }

        return childs;
    }


    /*  Helper functions for drag & drop functionality  */
    
    function task_draggable_helper()
    {
        if (!task_draghelper)
            task_draghelper = $('<div class="taskitem-draghelper">&#x2714;</div>');

        return task_draghelper;
    }

    function task_draggable_start(event, ui)
    {
        var opts = {
            hoverClass: 'droptarget',
            accept: task_droppable_accept,
            drop: task_draggable_dropped,
            addClasses: false
        };

        $('.taskhead, #rootdroppable').droppable(opts);
        tasklists_widget.droppable(opts);

        $(this).parent().addClass('dragging');
        $('#rootdroppable').show();

        // enable auto-scrolling of list container
        var container = $(rcmail.gui_objects.resultlist);
        if (container.height() > container.parent().height()) {
            task_drag_active = true;
            list_scroll_top = container.parent().scrollTop();
        }
    }

    function task_draggable_move(event, ui)
    {
        var scroll = 0,
            mouse = rcube_event.get_mouse_pos(event),
            container = $(rcmail.gui_objects.resultlist);

        mouse.y -= container.parent().offset().top;

        if (mouse.y < scroll_sensitivity && list_scroll_top > 0) {
            scroll = -1; // up
        }
        else if (mouse.y > container.parent().height() - scroll_sensitivity) {
            scroll = 1; // down
        }

        if (task_drag_active && scroll != 0) {
            if (!scroll_timer)
                scroll_timer = window.setTimeout(function(){ tasklist_drag_scroll(container, scroll); }, scroll_delay);
        }
        else if (scroll_timer) {
            window.clearTimeout(scroll_timer);
            scroll_timer = null;
        }
    }

    function task_draggable_stop(event, ui)
    {
        $(this).parent().removeClass('dragging');
        $('#rootdroppable').hide();
        task_drag_active = false;
    }

    function task_droppable_accept(draggable)
    {
        if (rcmail.busy)
            return false;

        var drag_id = draggable.data('id'),
            drop_id = $(this).data('id'),
            drag_rec = listdata[drag_id] || {},
            drop_rec = listdata[drop_id];

        // drop target is another list
        if (drag_rec && $(this).data('type') == 'tasklist') {
            var  drop_list = me.tasklists[drop_id],
               from_list = me.tasklists[drag_rec.list];
            return !drag_rec.parent_id && drop_id != drag_rec.list && drop_list && drop_list.editable && from_list && from_list.editable;
        }

        if (drop_rec && drop_rec.list != drag_rec.list)
            return false;

        if (drop_id == drag_rec.parent_id)
            return false;

        while (drop_rec && drop_rec.parent_id) {
            if (drop_rec.parent_id == drag_id)
                return false;
            drop_rec = listdata[drop_rec.parent_id];
        }

        return true;
    }

    function task_draggable_dropped(event, ui)
    {
        var drop_id = $(this).data('id'),
            task_id = ui.draggable.data('id'),
            rec = listdata[task_id],
            parent, li;

        // dropped on another list -> move
        if ($(this).data('type') == 'tasklist') {
            if (rec) {
                save_task({ id:rec.id, list:drop_id, _fromlist:rec.list }, 'move');
                rec.list = drop_id;
            }
        }
        // dropped on a new parent task or root
        else {
            parent = drop_id ? $('li[rel="'+drop_id+'"] > ul.childtasks', rcmail.gui_objects.resultlist) : $(rcmail.gui_objects.resultlist)

            if (rec && parent.length) {
                // submit changes to server
                rec._old_parent_id = rec.parent_id;
                rec.parent_id = drop_id || 0;
                save_task(rec, 'edit');

                li = ui.draggable.parent();
                li.slideUp(300, function(){
                    li.appendTo(parent);
                    resort_task(rec, li);
                    li.slideDown(300);
                    fix_tree_toggles();
                });
            }
        }
    }

    /**
     * Scroll list container in the given direction
     */
    function tasklist_drag_scroll(container, dir)
    {
        if (!task_drag_active)
            return;

        var old_top = list_scroll_top;
        container.parent().get(0).scrollTop += scroll_step * dir;
        list_scroll_top = container.parent().scrollTop();
        scroll_timer = null;

        if (list_scroll_top != old_top)
            scroll_timer = window.setTimeout(function(){ tasklist_drag_scroll(container, dir); }, scroll_speed);
    }

    // check if the task has 'real' attendees, excluding the current user
    var has_attendees = function(task)
    {
        return !!(task.attendees && task.attendees.length && (task.attendees.length > 1 || String(task.attendees[0].email).toLowerCase() != settings.identity.email));
    };

    // check if the current user is an attendee of this task
    var is_attendee = function(task, email, role)
    {
        var i, attendee, emails = email ? ';' + email.toLowerCase() : settings.identity.emails;

        for (i=0; task.attendees && i < task.attendees.length; i++) {
            attendee = task.attendees[i];
            if ((!role || attendee.role == role) && attendee.email && emails.indexOf(';'+attendee.email.toLowerCase()) >= 0) {
                return attendee;
            }
        }

        return false;
    };

    // check if the current user is the organizer
    var is_organizer = function(task, email)
    {
        if (!email) email = task.organizer ? task.organizer.email : null;
        if (email)
            return settings.identity.emails.indexOf(';'+email) >= 0;
        return true;
    };

    // add the given list of participants
    var add_attendees = function(names, params)
    {
        names = explode_quoted_string(names.replace(/,\s*$/, ''), ',');

        // parse name/email pairs
        var i, item, email, name, success = false;
        for (i=0; i < names.length; i++) {
            email = name = '';
            item = $.trim(names[i]);

            if (!item.length) {
                continue;
            }
            // address in brackets without name (do nothing)
            else if (item.match(/^<[^@]+@[^>]+>$/)) {
                email = item.replace(/[<>]/g, '');
            }
            // address without brackets and without name (add brackets)
            else if (rcube_check_email(item)) {
                email = item;
            }
            // address with name
            else if (item.match(/([^\s<@]+@[^>]+)>*$/)) {
                email = RegExp.$1;
                name = item.replace(email, '').replace(/^["\s<>]+/, '').replace(/["\s<>]+$/, '');
            }

            if (email) {
                add_attendee($.extend({ email:email, name:name }, params));
                success = true;
            }
            else {
                alert(rcmail.gettext('noemailwarning'));
            }
        }

        return success;
    };

    // add the given attendee to the list
    var add_attendee = function(data, readonly, before)
    {
        if (!me.selected_task)
            return false;

        // check for dupes...
        var exists = false;
        $.each(task_attendees, function(i, v) { exists |= (v.email == data.email); });
        if (exists)
            return false;

        var dispname = Q(data.name || data.email);
        if (data.email)
            dispname = '<a href="mailto:' + data.email + '" title="' + Q(data.email) + '" class="mailtolink" data-cutype="' + data.cutype + '">' + dispname + '</a>';

        // delete icon
        var icon = rcmail.env.deleteicon ? '<img src="' + rcmail.env.deleteicon + '" alt="" />' : rcmail.gettext('delete');
        var dellink = '<a href="#delete" class="iconlink delete deletelink" title="' + Q(rcmail.gettext('delete')) + '">' + icon + '</a>';
        var tooltip = data.status || '';

        // send invitation checkbox
        var invbox = '<input type="checkbox" class="edit-attendee-reply" value="' + Q(data.email) +'" title="' + Q(rcmail.gettext('tasklist.sendinvitations')) + '" '
            + (!data.noreply && settings.itip_notify & 1 ? 'checked="checked" ' : '') + '/>';

        if (data['delegated-to'])
            tooltip = rcmail.gettext('delegatedto', 'tasklist') + data['delegated-to'];
        else if (data['delegated-from'])
            tooltip = rcmail.gettext('delegatedfrom', 'tasklist') + data['delegated-from'];

        // add expand button for groups
        if (data.cutype == 'GROUP') {
            dispname += ' <a href="#expand" data-email="' + Q(data.email) + '" class="iconbutton add expandlink" title="' + rcmail.gettext('expandattendeegroup','libcalendaring') + '">' +
                rcmail.gettext('expandattendeegroup','libcalendaring') + '</a>';
        }

        var html = '<td class="name"><span class="attendee-name">' + dispname + '</span></td>' +
            '<td class="confirmstate"><span class="' + String(data.status).toLowerCase() + '" title="' + Q(tooltip) + '">' + Q(data.status || '') + '</span></td>' +
            (data.cutype != 'RESOURCE' ? '<td class="invite">' + (readonly || !invbox ? '' : invbox) + '</td>' : '') +
            '<td class="options">' + (readonly ? '' : dellink) + '</td>';

        var tr = $('<tr>')
            .addClass(String(data.role).toLowerCase())
            .html(html);

        if (before)
            tr.insertBefore(before)
        else
            tr.appendTo(attendees_list);

        tr.find('a.deletelink').click({ id:(data.email || data.name) }, function(e) { remove_attendee(this, e.data.id); return false; });
        tr.find('a.mailtolink').click(task_attendee_click);
        tr.find('a.expandlink').click(data, function(e) { me.expand_attendee_group(e, add_attendee, remove_attendee); return false; });
        tr.find('input.edit-attendee-reply').click(function() {
            var enabled = $('#edit-attendees-invite:checked').length || $('input.edit-attendee-reply:checked').length;
            $('#taskeditform .attendees-commentbox')[enabled ? 'show' : 'hide']();
        });

        task_attendees.push(data);
        return true;
    };

    // event handler for clicks on an attendee link
    var task_attendee_click = function(e)
    {
        var mailto = this.href.substr(7);
        rcmail.command('compose', mailto);

        return false;
    };

    // remove an attendee from the list
    var remove_attendee = function(elem, id)
    {
      $(elem).closest('tr').remove();
      task_attendees = $.grep(task_attendees, function(data) { return (data.name != id && data.email != id) });
    };

    /**
     * Show task details in a dialog
     */
    function task_show_dialog(id, data, temp)
    {
        var $dialog = $('#taskshow'), rec, list;

        if ($dialog.is(':ui-dialog'))
          $dialog.dialog('close');

        // remove status-* classes
        $dialog.removeClass(function(i, oldclass) {
            var oldies = String(oldclass).split(' ');
            return $.grep(oldies, function(cls) { return cls.indexOf('status-') === 0 }).join(' ');
        });

        if (!(rec = (data || listdata[id])) || (rcmail.menu_stack && rcmail.menu_stack.length > 0))
            return;

        me.selected_task = rec;
        list = rec.list && me.tasklists[rec.list] ? me.tasklists[rec.list] : {};

        // fill dialog data
        $('#task-parent-title').html(Q(rec.parent_title || '')+' &raquo;').css('display', rec.parent_title ? 'block' : 'none');
        $('#task-title').html(text2html(Q(rec.title || '')));
        $('#task-description').html(text2html(rec.description || '', 300, 6))[(rec.description ? 'show' : 'hide')]();
        $('#task-date')[(rec.date ? 'show' : 'hide')]().children('.task-text').html(Q(rec.date || rcmail.gettext('nodate','tasklist')));
        $('#task-time').html(Q(rec.time || ''));
        $('#task-start')[(rec.startdate ? 'show' : 'hide')]().children('.task-text').html(Q(rec.startdate || ''));
        $('#task-starttime').html(Q(rec.starttime || ''));
        $('#task-alarm')[(rec.alarms_text ? 'show' : 'hide')]().children('.task-text').html(Q(rec.alarms_text));
        $('#task-completeness .task-text').html(((rec.complete || 0) * 100) + '%');
        $('#task-status')[(rec.status ? 'show' : 'hide')]().children('.task-text').html(rcmail.gettext('status-'+String(rec.status).toLowerCase(),'tasklist'));
        $('#task-list .task-text').html(Q(me.tasklists[rec.list] ? me.tasklists[rec.list].name : ''));
        $('#task-attendees, #task-organizer, #task-created-changed, #task-rsvp, #task-rsvp-comment').hide();

        var itags = get_inherited_tags(rec);
        var taglist = $('#task-tags')[(rec.tags && rec.tags.length || itags.length ? 'show' : 'hide')]().children('.task-text').empty();
        if (rec.tags && rec.tags.length) {
            $.each(rec.tags, function(i,val){
                $('<span>').addClass('tag-element').html(Q(val)).appendTo(taglist);
            });
        }

        // append inherited tags
        if (itags.length) {
            $.each(itags, function(i,val){
                if (!rec.tags || $.inArray(val, rec.tags) < 0)
                    $('<span>').addClass('tag-element inherit').html(Q(val)).appendTo(taglist);
            });
            // re-sort tags list
            $(taglist).children().sortElements(function(a,b){
                return $.text([a]).toLowerCase() > $.text([b]).toLowerCase() ? 1 : -1;
            });
        }

        if (rec.status) {
          $dialog.addClass('status-' + String(rec.status).toLowerCase());
        }

        if (rec.flagged) {
          $dialog.addClass('status-flagged');
        }

        if (rec.recurrence && rec.recurrence_text) {
            $('#task-recurrence').show().children('.task-text').html(Q(rec.recurrence_text));
        }
        else {
            $('#task-recurrence').hide();
        }

        if (rec.created || rec.changed) {
            $('#task-created-changed .task-created').html(Q(rec.created_ || rcmail.gettext('unknown','tasklist')))
            $('#task-created-changed .task-changed').html(Q(rec.changed_ || rcmail.gettext('unknown','tasklist')))
            $('#task-created-changed').show()
        }

        // build attachments list
        $('#task-attachments').hide();
        if ($.isArray(rec.attachments)) {
            task_show_attachments(rec.attachments || [], $('#task-attachments').children('.task-text'), rec);
            if (rec.attachments.length > 0) {
                $('#task-attachments').show();
            }
        }

        // build attachments list
        $('#task-links').hide();
        if ($.isArray(rec.links) && rec.links.length) {
            render_message_links(rec.links || [], $('#task-links').children('.task-text'), false, 'tasklist');
            $('#task-links').show();
        }

        // list task attendees
        if (list.attendees && rec.attendees) {
/*
            // sort resources to the end
            rec.attendees.sort(function(a,b) {
                var j = a.cutype == 'RESOURCE' ? 1 : 0,
                    k = b.cutype == 'RESOURCE' ? 1 : 0;
                return (j - k);
            });
*/
            var j, data, rsvp = false, mystatus = null, line, morelink, html = '', overflow = '',
                organizer = is_organizer(rec);

            for (j=0; j < rec.attendees.length; j++) {
                data = rec.attendees[j];

                if (data.email && settings.identity.emails.indexOf(';'+data.email) >= 0) {
                    mystatus = data.status.toLowerCase();
                    if (data.status == 'NEEDS-ACTION' || data.status == 'TENTATIVE' || data.rsvp)
                        rsvp = mystatus;
                }

                line = task_attendee_html(data);

                if (morelink)
                    overflow += line;
                else
                    html += line;

                // stop listing attendees
                if (j == 7 && rec.attendees.length >= 7) {
                    morelink = $('<a href="#more" class="morelink"></a>').html(rcmail.gettext('andnmore', 'tasklist').replace('$nr', rec.attendees.length - j - 1));
                }
            }

            if (html) {
                $('#task-attendees').show()
                    .children('.task-text')
                    .html(html)
                    .find('a.mailtolink').click(task_attendee_click);

                // display all attendees in a popup when clicking the "more" link
                if (morelink) {
                    $('#task-attendees .task-text').append(morelink);
                    morelink.click(function(e) {
                        rcmail.show_popup_dialog(
                            '<div id="all-task-attendees" class="task-attendees">' + html + overflow + '</div>',
                            rcmail.gettext('tabattendees', 'tasklist'),
                            null,
                            {width: 450, modal: false}
                        );
                        $('#all-task-attendees a.mailtolink').click(task_attendee_click);
                        return false;
                    });
                }
            }
/*
            if (mystatus && !rsvp) {
                $('#task-partstat').show().children('.changersvp')
                    .removeClass('accepted tentative declined delegated needs-action')
                    .addClass(mystatus)
                    .children('.task-text')
                    .html(Q(rcmail.gettext('itip' + mystatus, 'libcalendaring')));
            }
*/
            var show_rsvp = !temp && rsvp && list.editable && !is_organizer(rec) && rec.status != 'CANCELLED';
            $('#task-rsvp')[(show_rsvp ? 'show' : 'hide')]();
            $('#task-rsvp .rsvp-buttons input').prop('disabled', false).filter('input[rel='+mystatus+']').prop('disabled', true);

            if (show_rsvp && rec.comment) {
                $('#task-rsvp-comment').show().children('.task-text').html(Q(rec.comment));
            }
            $('#task-rsvp a.reply-comment-toggle').show();
            $('#task-rsvp .itip-reply-comment textarea').hide().val('');

            if (rec.organizer && !organizer) {
                $('#task-organizer').show().children('.task-text').html(task_attendee_html($.extend(rec.organizer, { role:'ORGANIZER' })));
            }
        }

        // define dialog buttons
        var buttons = [];
        if (list.editable && !rec.readonly) {
              buttons.push({
                text: rcmail.gettext('edit','tasklist'),
                click: function() {
                    task_edit_dialog(me.selected_task.id, 'edit');
                },
                disabled: rcmail.busy
            });
        }

        if (has_permission(list, 'td') && !rec.readonly) {
            buttons.push({
                text: rcmail.gettext('delete','tasklist'),
                'class': 'delete',
                click: function() {
                    if (delete_task(me.selected_task.id))
                        $dialog.dialog('close');
                },
                disabled: rcmail.busy
            });
        }

        // open jquery UI dialog
        $dialog.dialog({
          modal: false,
          resizable: true,
          closeOnEscape: true,
          title: rcmail.gettext('taskdetails', 'tasklist'),
          open: function() {
              $dialog.parent().find('.ui-button:not(.ui-dialog-titlebar-close)').first().focus();
          },
          close: function() {
              $dialog.dialog('destroy').appendTo(document.body);
              $('.libcal-rsvp-replymode').hide();
          },
          dragStart: function() {
              $('.libcal-rsvp-replymode').hide();
          },
          resizeStart: function() {
              $('.libcal-rsvp-replymode').hide();
          },
          buttons: buttons,
          minWidth: 500,
          width: 580
        }).show();

        // set dialog size according to content
        me.dialog_resize($dialog.get(0), $dialog.height(), 580);
    }

    // render HTML code for displaying an attendee record
    function task_attendee_html(data)
    {
        var dispname = Q(data.name || data.email), tooltip = '';

        if (data.email) {
          tooltip = data.email;
          dispname = '<a href="mailto:' + data.email + '" class="mailtolink" data-cutype="' + data.cutype + '">' + dispname + '</a>';
        }

        if (data['delegated-to'])
          tooltip = rcmail.gettext('delegatedto', 'tasklist') + data['delegated-to'];
        else if (data['delegated-from'])
          tooltip = rcmail.gettext('delegatedfrom', 'tasklist') + data['delegated-from'];

        return '<span class="attendee ' + String(data.role == 'ORGANIZER' ? 'organizer' : data.status).toLowerCase() + '" title="' + Q(tooltip) + '">' + dispname + '</span> ';
    }

    /**
     *
     */
    function task_history_dialog()
    {
        var dialog, rec = me.selected_task;
        if (!rec || !rec.id || !window.libkolab_audittrail) {
            return false;
        }

        // render dialog
        $dialog = libkolab_audittrail.object_history_dialog({
            module: 'tasklist',
            container: '#taskhistory',
            title: rcmail.gettext('objectchangelog','tasklist') + ' - ' + rec.title,

            // callback function for list actions
            listfunc: function(action, rev) {
                var rec = $dialog.data('rec');
                saving_lock = rcmail.set_busy(true, 'loading', saving_lock);
                rcmail.http_post('task', { action: action, t: { id: rec.id, list:rec.list, rev: rev } }, saving_lock);
            },

            // callback function for comparing two object revisions
            comparefunc: function(rev1, rev2) {
                var rec = $dialog.data('rec');
                saving_lock = rcmail.set_busy(true, 'loading', saving_lock);
                rcmail.http_post('task', { action:'diff', t: { id: rec.id, list: rec.list, rev1: rev1, rev2: rev2 } }, saving_lock);
            }
        });

        $dialog.data('rec', rec);

        // fetch changelog data
        saving_lock = rcmail.set_busy(true, 'loading', saving_lock);
        rcmail.http_post('task', { action: 'changelog', t: { id: rec.id, list: rec.list } }, saving_lock);
    }

    /**
     *
     */
    function task_render_changelog(data)
    {
        var $dialog = $('#taskhistory'),
            rec = $dialog.data('rec');

        if (data === false || !data.length || !rec) {
          // display 'unavailable' message
          $('<div class="notfound-message task-dialog-message warning">' + rcmail.gettext('objectchangelognotavailable','tasklist') + '</div>')
            .insertBefore($dialog.find('.changelog-table').hide());
          return;
        }

        data.module = 'tasklist';
        libkolab_audittrail.render_changelog(data, rec, me.tasklists[rec.list]);

        // set dialog size according to content
        me.dialog_resize($dialog.get(0), $dialog.height(), 600);
    }

    /**
     *
     */
    function task_show_diff(data)
    {
        var rec = me.selected_task,
            $dialog = $("#taskdiff");

        $dialog.find('div.form-section, h2.task-title-new').hide().data('set', false).find('.index').html('');
        $dialog.find('div.form-section.clone').remove();

        // always show event title and date
        $('.task-title', $dialog).text(rec.title).removeClass('task-text-old').show();

        // show each property change
        $.each(data.changes, function(i, change) {
            var prop = change.property, r2, html = false,
                row = $('div.task-' + prop, $dialog).first();

                // special case: title
                if (prop == 'title') {
                    $('.task-title', $dialog).addClass('task-text-old').text(change['old'] || '--');
                    $('.task-title-new', $dialog).text(change['new'] || '--').show();
                }

              // no display container for this property
              if (!row.length) {
                  return true;
              }

              // clone row if already exists
              if (row.data('set')) {
                  r2 = row.clone().addClass('clone').insertAfter(row);
                  row = r2;
              }

              // render description text
              if (prop == 'description') {
                  if (!change.diff_ && change['old']) change.old_ = text2html(change['old']);
                  if (!change.diff_ && change['new']) change.new_ = text2html(change['new']);
                  html = true;
              }
              // format attendees struct
              else if (prop == 'attendees') {
                  if (change['old']) change.old_ = task_attendee_html(change['old']);
                  if (change['new']) change.new_ = task_attendee_html($.extend({}, change['old'] || {}, change['new']));
                  html = true;
              }
              // localize status
              else if (prop == 'status') {
                  if (change['old']) change.old_ = rcmail.gettext('status-'+String(change['old']).toLowerCase(), 'tasklist');
                  if (change['new']) change.new_ = rcmail.gettext('status-'+String(change['new']).toLowerCase(), 'tasklist');
              }

              // format attachments struct
              if (prop == 'attachments') {
                  if (change['old']) task_show_attachments([change['old']], row.children('.task-text-old'), rec, false);
                  else               row.children('.task-text-old').text('--');
                  if (change['new']) task_show_attachments([$.extend({}, change['old'] || {}, change['new'])], row.children('.task-text-new'), rec, false);
                  else               row.children('.task-text-new').text('--');
                  // remove click handler in diff view
                  $('.attachmentslist li a', row).unbind('click').removeAttr('href');
              }
              else if (change.diff_) {
                  row.children('.task-text-diff').html(change.diff_);
                  row.children('.task-text-old, .task-text-new').hide();
              }
              else {
                  if (!html) {
                    // escape HTML characters
                    change.old_ = Q(change.old_ || change['old'] || '--')
                    change.new_ = Q(change.new_ || change['new'] || '--')
                  }
                  row.children('.task-text-old').html(change.old_ || change['old'] || '--').show();
                  row.children('.task-text-new').html(change.new_ || change['new'] || '--').show();
              }

              // display index number
              if (typeof change.index != 'undefined') {
                  row.find('.index').html('(' + change.index + ')');
              }

              row.show().data('set', true);
        });

        // open jquery UI dialog
        $dialog.dialog({
            modal: false,
            resizable: true,
            closeOnEscape: true,
            title: rcmail.gettext('objectdiff','tasklist').replace('$rev1', data.rev1).replace('$rev2', data.rev2) + ' - ' + rec.title,
            open: function() {
                $dialog.attr('aria-hidden', 'false');
            },
            close: function() {
                $dialog.dialog('destroy').attr('aria-hidden', 'true').hide();
            },
            buttons: [
                {
                    text: rcmail.gettext('close'),
                    click: function() { $dialog.dialog('close'); },
                    autofocus: true
                }
            ],
            minWidth: 320,
            width: 450
        }).show();

        // set dialog size according to content
        me.dialog_resize($dialog.get(0), $dialog.height(), 400);
    }

    // close the event history dialog
    function close_history_dialog()
    {
        $('#taskhistory, #taskdiff').each(function(i, elem) {
        var $dialog = $(elem);
        if ($dialog.is(':ui-dialog'))
            $dialog.dialog('close');
        });
    };

    /**
     * Opens the dialog to edit a task
     */
    function task_edit_dialog(id, action, presets)
    {
        $('#taskshow:ui-dialog').dialog('close');

        var rec = listdata[id] || presets,
            $dialog = $('<div>'),
            editform = $('#taskedit'),
            list = rec.list && me.tasklists[rec.list] ? me.tasklists[rec.list] :
                (me.selected_list ? me.tasklists[me.selected_list] : { editable: action == 'new', rights: action == 'new' ? 'rwitd' : 'r' });

        if (rcmail.busy || !has_permission(list, 'i') || (action == 'edit' && (!rec || rec.readonly)))
            return false;

        me.selected_task = $.extend({ valarms:[] }, rec);  // clone task object
        rec = me.selected_task;

        // assign temporary id
        if (!me.selected_task.id)
            me.selected_task.id = -(++idcount);

        // reset dialog first
        $('#taskeditform').get(0).reset();

        // fill form data
        var title = $('#taskedit-title').val(rec.title || '');
        var description = $('#taskedit-description').val(rec.description || '');
        var recdate = $('#taskedit-date').val(rec.date || '');
        var rectime = $('#taskedit-time').val(rec.time || '');
        var recstartdate = $('#taskedit-startdate').val(rec.startdate || '');
        var recstarttime = $('#taskedit-starttime').val(rec.starttime || '');
        var complete = $('#taskedit-completeness').val((rec.complete || 0) * 100);
        completeness_slider.slider('value', complete.val());
        var taskstatus = $('#taskedit-status').val(rec.status || '');
        var tasklist = $('#taskedit-tasklist').val(rec.list || me.selected_list).prop('disabled', rec.parent_id ? true : false);
        var notify = $('#edit-attendees-donotify').get(0);
        var invite = $('#edit-attendees-invite').get(0);
        var comment = $('#edit-attendees-comment');

        invite.checked = settings.itip_notify & 1 > 0;
        notify.checked = has_attendees(rec) && invite.checked;

        // set tasklist selection according to permissions
        tasklist.find('option').each(function(i, opt) {
            var l = me.tasklists[opt.value] || {};
            $(opt).prop('disabled', !(l.editable || (action == 'new' && has_permission(l, 'i'))));
        });

        // tag-edit line
        var tagline = $(rcmail.gui_objects.edittagline).empty();
        $.each(typeof rec.tags == 'object' && rec.tags.length ? rec.tags : [''], function(i,val){
            $('<input>')
                .attr('name', 'tags[]')
                .attr('tabindex', '0')
                .addClass('tag')
                .val(val)
                .appendTo(tagline);
        });

        $('input.tag', rcmail.gui_objects.edittagline).tagedit({
            animSpeed: 100,
            allowEdit: false,
            checkNewEntriesCaseSensitive: false,
            autocompleteOptions: { source: tags, minLength: 0, noCheck: true, appendTo:'#taskedit' },
            texts: { removeLinkTitle: rcmail.gettext('removetag', 'tasklist') }
        });

        // set alarm(s)
        me.set_alarms_edit('#taskedit-alarms', action != 'new' && rec.valarms ? rec.valarms : []);

        if ($.isArray(rec.links) && rec.links.length) {
            render_message_links(rec.links, $('#taskedit-links .task-text'), true, 'tasklist');
            $('#taskedit-links').show();
        }
        else {
            $('#taskedit-links').hide();
        }

        // set recurrence
        me.set_recurrence_edit(rec);

        // init attendees tab
        var organizer = !rec.attendees || is_organizer(rec),
            allow_invitations = organizer || (rec.owner && rec.owner == 'anonymous') || settings.invite_shared;

        task_attendees = [];
        attendees_list = $('#edit-attendees-table > tbody').html('');
        $('#edit-attendees-notify')[(allow_invitations && has_attendees(rec) && (settings.itip_notify & 2) ? 'show' : 'hide')]();
        $('#edit-localchanges-warning')[(has_attendees(rec) && !(allow_invitations || (rec.owner && is_organizer(rec, rec.owner))) ? 'show' : 'hide')]();

        // attendees (aka assignees)
        if (list.attendees) {
            var j, data, reply_selected = 0;
            if (rec.attendees) {
                for (j=0; j < rec.attendees.length; j++) {
                    data = rec.attendees[j];
                    add_attendee(data, !allow_invitations);
                    if (allow_invitations && !data.noreply) {
                        reply_selected++;
                    }
                }
            }

            // make sure comment box is visible if at least one attendee has reply enabled
            // or global "send invitations" checkbox is checked
            $('#taskeditform .attendees-commentbox')[(reply_selected || invite.checked ? 'show' : 'hide')]();

            // select the correct organizer identity
            var identity_id = 0;
            $.each(settings.identities, function(i,v) {
                if (!rec.organizer || v == rec.organizer.email) {
                    identity_id = i;
                    return false;
                }
            });

            $('#edit-tab-attendees').show();
            $('#edit-attendees-form')[(allow_invitations?'show':'hide')]();
            $('#edit-identities-list').val(identity_id);
            $('#taskedit-organizer')[(organizer ? 'show' : 'hide')]();
        }
        else {
            $('#edit-tab-attendees').hide();
        }

        // attachments
        rcmail.enable_command('remove-attachment', list.editable);
        me.selected_task.deleted_attachments = [];
        // we're sharing some code for uploads handling with app.js
        rcmail.env.attachments = [];
        rcmail.env.compose_id = me.selected_task.id; // for rcmail.async_upload_form()

        if ($.isArray(rec.attachments)) {
            task_show_attachments(rec.attachments, $('#taskedit-attachments'), rec, true);
        }
        else {
            $('#taskedit-attachments > ul').empty();
        }

        // show/hide tabs according to calendar's feature support
        $('#taskedit-tab-attachments')[(list.attachments||rec.attachments?'show':'hide')]();

        // activate the first tab
        $('#taskedit').tabs('option', 'active', 0);

        // define dialog buttons
        var buttons = [];
        buttons.push({
            text: rcmail.gettext('save', 'tasklist'),
            'class': 'mainaction',
            click: function() {
            var data = me.selected_task;
            data._status_before = me.selected_task.status + '';

            // copy form field contents into task object to save
            $.each({ title:title, description:description, date:recdate, time:rectime, startdate:recstartdate, starttime:recstarttime, status:taskstatus }, function(key,input){
                data[key] = input.val();
            });
            data.list = tasklist.find('option:selected').val();
            data.tags = [];
            data.attachments = [];
            data.attendees = task_attendees;
            data.valarms = me.serialize_alarms('#taskedit-alarms');
            data.recurrence = me.serialize_recurrence(rectime.val());

            // do some basic input validation
            if (!data.title || !data.title.length) {
                title.focus();
                return false;
            }
            else if (data.startdate && data.date) {
                var startdate = $.datepicker.parseDate(datepicker_settings.dateFormat, data.startdate, datepicker_settings);
                var duedate = $.datepicker.parseDate(datepicker_settings.dateFormat, data.date, datepicker_settings);
                if (startdate > duedate) {
                    alert(rcmail.gettext('invalidstartduedates', 'tasklist'));
                    return false;
                }
                else if ((data.time == '') != (data.starttime == '')) {
                    alert(rcmail.gettext('invalidstartduetimes', 'tasklist'));
                    return false;
                }
            }
            else if (data.recurrence && !data.startdate && !data.date) {
                alert(rcmail.gettext('recurrencerequiresdate', 'tasklist'));
                return false;
            }

            // collect tags
            $('input[type="hidden"]', rcmail.gui_objects.edittagline).each(function(i,elem) {
                if (elem.value)
                    data.tags.push(elem.value);
            });
            // including the "pending" one in the text box
            var newtag = $('#tagedit-input').val();
            if (newtag != '') {
                data.tags.push(newtag);
            }

            // uploaded attachments list
            for (var i in rcmail.env.attachments) {
                if (i.match(/^rcmfile(.+)/))
                    data.attachments.push(RegExp.$1);
            }

            // task assigned to a new list
            if (data.list && listdata[id] && data.list != listdata[id].list) {
                data._fromlist = list.id;
            }

            data.complete = complete.val() / 100;
            if (isNaN(data.complete))
                data.complete = null;
            else if (data.complete == 1.0 && rec.status === '')
                data.status = 'COMPLETED';

            if (!data.list && list.id)
                data.list = list.id;

            if (!data.tags.length)
                data.tags = '';

            if (organizer) {
                data._identity = $('#edit-identities-list option:selected').val();
                delete data.organizer;
            }

            // per-attendee notification suppression
            var need_invitation = false;
            if (allow_invitations) {
                $.each(data.attendees, function (i, v) {
                    if (v.role != 'ORGANIZER') {
                        if ($('input.edit-attendee-reply[value="' + v.email + '"]').prop('checked')) {
                            need_invitation = true;
                            delete data.attendees[i]['noreply'];
                        }
                        else if (settings.itip_notify > 0) {
                            data.attendees[i].noreply = 1;
                        }
                    }
                });
            }

            // tell server to send notifications
            if ((data.attendees.length || (rec.id && rec.attendees.length)) && allow_invitations && (notify.checked || invite.checked || need_invitation)) {
                data._notify = settings.itip_notify;
                data._comment = comment.val();
            }
            else if (data._notify) {
                delete data._notify;
            }

            if (save_task(data, action))
                $dialog.dialog('close');
        }  // end click:
        });

        if (action != 'new') {
            buttons.push({
                text: rcmail.gettext('delete', 'tasklist'),
                'class': 'delete',
                click: function() {
                    if (delete_task(rec.id))
                        $dialog.dialog('close');
                }
            });
        }

        buttons.push({
            text: rcmail.gettext('cancel', 'tasklist'),
            click: function() {
                $dialog.dialog('close');
            }
        });

        // open jquery UI dialog
        $dialog.dialog({
          modal: true,
          resizable: (!bw.ie6 && !bw.ie7),  // disable for performance reasons
          closeOnEscape: false,
          title: rcmail.gettext((action == 'edit' ? 'edittask' : 'newtask'), 'tasklist'),
          close: function() {
              rcmail.ksearch_blur();
              editform.hide().appendTo(document.body);
              $dialog.dialog('destroy').remove();
          },
          buttons: buttons,
          minHeight: 460,
          minWidth: 500,
          width: 580
        }).append(editform.show());  // adding form content AFTERWARDS massively speeds up opening on IE

        title.select();

        // set dialog size according to content
        me.dialog_resize($dialog.get(0), $dialog.height(), 580);
    }

    /**
     * Open a task attachment either in a browser window for inline view or download it
     */
    function load_attachment(rec, att)
    {
        // can't open temp attachments
        if (!rec.id || rec.id < 0)
            return false;

        var query = { _id: att.id, _t: rec.recurrence_id||rec.id, _list:rec.list, _frame: 1 };
        if (rec.rev)
            query._rev = rec.rev;


        // open attachment in frame if it's of a supported mimetype
        // similar as in app.js and calendar_ui.js
        if (att.id && att.mimetype && $.inArray(att.mimetype, settings.mimetypes)>=0) {
            if (rcmail.open_window(rcmail.url('get-attachment', query), true, true)) {
                return;
            }
        }

        query._frame = null;
        query._download = 1;
        rcmail.goto_url('get-attachment', query, false);
    };

    /**
     * Build task attachments list
     */
    function task_show_attachments(list, container, rec, edit)
    {
        var i, id, len, content, li, elem,
            ul = $('<ul>').addClass('attachmentslist');

        for (i=0, len=list.length; i<len; i++) {
            elem = list[i];
            li = $('<li>').addClass(elem.classname);

            if (edit) {
                rcmail.env.attachments[elem.id] = elem;
                // delete icon
                content = $('<a>')
                    .attr('href', '#delete')
                    .attr('title', rcmail.gettext('delete'))
                    .addClass('delete')
                    .click({ id:elem.id }, function(e) {
                        remove_attachment(this, e.data.id);
                        return false;
                    });

                if (!rcmail.env.deleteicon) {
                    content.html(rcmail.gettext('delete'));
                }
                else {
                    $('<img>').attr('src', rcmail.env.deleteicon).attr('alt', rcmail.gettext('delete')).appendTo(content);
                }

                li.append(content);
            }

            // name/link
            $('<a>')
                .attr('href', '#load')
                .addClass('file')
                .html(elem.name).click({ task:rec, att:elem }, function(e) {
                    load_attachment(e.data.task, e.data.att);
                    return false;
                }).appendTo(li);

            ul.append(li);
        }

        if (edit && rcmail.gui_objects.attachmentlist) {
            ul.id = rcmail.gui_objects.attachmentlist.id;
            rcmail.gui_objects.attachmentlist = ul.get(0);
        }

        container.empty().append(ul);
    };

    /**
     *
     */
    function remove_attachment(elem, id)
    {
        $(elem.parentNode).hide();
        me.selected_task.deleted_attachments.push(id);
        delete rcmail.env.attachments[id];
    }

    /**
     *
     */
    function remove_link(elem)
    {
        var $elem = $(elem), uri = $elem.attr('data-uri');

        // remove the link item matching the given uri
        me.selected_task.links = $.grep(me.selected_task.links, function(link) { return link.uri != uri; });

        // remove UI list item
        $elem.hide().closest('li').addClass('deleted');
    }

    /**
     *
     */
    function add_childtask(id)
    {
        if (rcmail.busy)
            return false;

        var rec = listdata[id];
        task_edit_dialog(null, 'new', { parent_id:id, list:rec.list });
    }

    /**
     * Delete the given task
     */
    function delete_task(id)
    {
        var rec = listdata[id];
        if (!rec || rec.readonly || rcmail.busy)
            return false;

        var html, buttons = [], $dialog = $('<div>');

        // Subfunction to submit the delete command after confirm
        var _delete_task = function(id, mode) {
            var rec = listdata[id],
                li = $('li[rel="'+id+'"]', rcmail.gui_objects.resultlist).hide(),
                decline = $dialog.find('input.confirm-attendees-decline:checked').length,
                notify = $dialog.find('input.confirm-attendees-notify:checked').length;

            saving_lock = rcmail.set_busy(true, 'tasklist.savingdata');
            rcmail.http_post('task', { action:'delete', t:{ id:rec.id, list:rec.list, _decline:decline, _notify:notify }, mode:mode, filter:filtermask });

            // move childs to parent/root
            if (mode != 1 && rec.children !== undefined) {
                var parent_node = rec.parent_id ? $('li[rel="'+rec.parent_id+'"] > .childtasks', rcmail.gui_objects.resultlist) : null;
                if (!parent_node || !parent_node.length)
                    parent_node = rcmail.gui_objects.resultlist;

                $.each(rec.children, function(i,cid) {
                    var child = listdata[cid];
                    child.parent_id = rec.parent_id;
                    resort_task(child, $('li[rel="'+cid+'"]').appendTo(parent_node), true);
                });
            }

            li.remove();
            delete listdata[id];
        }

        if (rec.children && rec.children.length) {
            html = rcmail.gettext('deleteparenttasktconfirm','tasklist');
            buttons.push({
                text: rcmail.gettext('deletethisonly','tasklist'),
                click: function() {
                    _delete_task(id, 0);
                    $(this).dialog('close');
                }
            });
            buttons.push({
                text: rcmail.gettext('deletewithchilds','tasklist'),
                click: function() {
                    _delete_task(id, 1);
                    $(this).dialog('close');
                }
            });
        }
        else {
            html = rcmail.gettext('deletetasktconfirm','tasklist');
            buttons.push({
                text: rcmail.gettext('delete','tasklist'),
                'class': 'delete',
                click: function() {
                    _delete_task(id, 0);
                    $(this).dialog('close');
                }
            });
        }

        if (is_attendee(rec)) {
            html += '<div class="task-dialog-message">' +
                '<label><input class="confirm-attendees-decline" type="checkbox" checked="checked" value="1" name="_decline" />&nbsp;' +
                    rcmail.gettext('itipdeclinetask', 'tasklist') + 
                '</label></div>';
        }
        else if (has_attendees(rec) && is_organizer(rec)) {
            html += '<div class="task-dialog-message">' +
                '<label><input class="confirm-attendees-notify" type="checkbox" checked="checked" value="1" name="_notify" />&nbsp;' +
                    rcmail.gettext('sendcancellation', 'tasklist') + 
                '</label></div>';
        }

        buttons.push({
            text: rcmail.gettext('cancel', 'tasklist'),
            click: function() {
                $(this).dialog('close');
            }
        });

        $dialog.html(html);
        $dialog.dialog({
          modal: true,
          width: 520,
          dialogClass: 'warning no-close',
          title: rcmail.gettext('deletetask', 'tasklist'),
          buttons: buttons,
          close: function(){
              $dialog.dialog('destroy').hide();
          }
        }).addClass('tasklist-confirm').show();

        return true;
    }

    /**
     * Check if the given task matches the current filtermask and tag selection
     */
    function match_filter(rec, cache, recursive)
    {
        // return cached result
        if (typeof cache[rec.id] != 'undefined' && recursive != 2) {
            return cache[rec.id];
        }

        var match = !filtermask || (filtermask & rec.mask) == filtermask;

        // in focusview mode, only tasks from the selected list are allowed
        if (focusview)
            match = $.inArray(rec.list, focusview_lists) >= 0 && match;

        if (match && tagsfilter.length) {
            match = rec.tags && rec.tags.length;
            var alltags = get_inherited_tags(rec).concat(rec.tags || []);
            for (var i=0; match && i < tagsfilter.length; i++) {
                if ($.inArray(tagsfilter[i], alltags) < 0)
                    match = false;
            }
        }

        // check if a child task matches the tags
        if (!match && (recursive||0) < 2 && rec.children && rec.children.length) {
            for (var j=0; !match && j < rec.children.length; j++) {
                match = match_filter(listdata[rec.children[j]], cache, 1);
            }
        }

        // walk up the task tree and check if a parent task matches
        var parent_id;
        if (!match && !recursive && (parent_id = rec.parent_id)) {
            while (!match && parent_id && listdata[parent_id]) {
                match = match_filter(listdata[parent_id], cache, 2);
                parent_id = listdata[parent_id].parent_id;
            }
        }

        if (recursive != 1) {
            cache[rec.id] = match;
        }
        return match;
    }

    /**
     *
     */
    function get_inherited_tags(rec)
    {
        var parent_id, itags = [];

        if ((parent_id = rec.parent_id)) {
            while (parent_id && listdata[parent_id]) {
                itags = itags.concat(listdata[parent_id].tags || []);
                parent_id = listdata[parent_id].parent_id;
            }
        }

        return $.unqiqueStrings(itags);
    }

    /**
     * Change tasks list sorting
     */
    function list_set_sort(col)
    {
        if (settings.sort_col != col) {
          settings.sort_col = col;
          $('#taskviewsortmenu .sortcol').attr('aria-checked', 'false').removeClass('selected')
              .filter('.by-' + col).attr('aria-checked', 'true').addClass('selected');

          // re-sort list index and re-render list
          listindex.sort(function(a, b) { return task_cmp(listdata[a], listdata[b]); });
          render_tasklist();

          rcmail.enable_command('list-order', settings.sort_col != 'auto');
          $('#taskviewsortmenu .sortorder').removeClass('selected').filter('[aria-checked=true]').addClass('selected');

          rcmail.save_pref({ name: 'tasklist_sort_col', value: (col == 'auto' ? '' : col) });
        }
    }

    /**
     * Change tasks list sort order
     */
    function list_set_order(order)
    {
        if (settings.sort_order != order) {
          settings.sort_order = order;
          $('#taskviewsortmenu .sortorder').attr('aria-checked', 'false').removeClass('selected')
              .filter('.' + order).attr('aria-checked', 'true').addClass('selected');

          // re-sort list index and re-render list
          listindex.sort(function(a, b) { return task_cmp(listdata[a], listdata[b]); });
          render_tasklist();

          rcmail.save_pref({ name: 'tasklist_sort_order', value: order });
        }
    }

    /**
     *
     */
    function list_edit_dialog(id)
    {
        var list = me.tasklists[id],
            $dialog = $(rcmail.gui_containers.tasklistform);

        if ($dialog.is(':ui-dialog'))
            $dialog.dialog('close');

        if (!list)
            list = { name:'', editable:true, rights:'riwta', showalarms:true };

        var editform, name, alarms;

        $dialog.html(rcmail.get_label('loading'));
        $.ajax({
            type: 'GET',
            dataType: 'html',
            url: rcmail.url('tasklist'),
            data: { action:(list.id ? 'form-edit' : 'form-new'), l:{ id:list.id } },
            success: function(data) {
                $dialog.html(data);
                rcmail.triggerEvent('tasklist_editform_load', list);

                // resize and reposition dialog window
                editform = $('#tasklisteditform');
                me.dialog_resize(rcmail.gui_containers.tasklistform, editform.height(), editform.width());

                name = $('#taskedit-tasklistame').prop('disabled', !has_permission(list, 'a')||list.norename).val(list.editname || list.name);
                alarms = $('#taskedit-showalarms').prop('checked', list.showalarms).get(0);
                name.select();

                // suppress form submission with <Enter>
                editform.on('submit', function(e) {
                    e.preventDefault();
                    return false;
                });
            }
        });

        // dialog buttons
        var buttons = [];

        buttons.push({
            text: rcmail.gettext('save','tasklist'),
            'class': 'mainaction',
            click: function() {
            // do some input validation
            if (!name.val() || name.val().length < 2) {
                alert(rcmail.gettext('invalidlistproperties', 'tasklist'));
                name.select();
                return;
            }

            // post data to server
            var data = editform.serializeJSON();
            if (list.id)
                data.id = list.id;
            if (alarms)
                data.showalarms = alarms.checked ? 1 : 0;

            saving_lock = rcmail.set_busy(true, 'tasklist.savingdata');
            rcmail.http_post('tasklist', { action:(list.id ? 'edit' : 'new'), l:data });
            $dialog.dialog('close');
        }  // end click:
        });

        buttons.push({
            text: rcmail.gettext('cancel','tasklist'),
            click: function() {
                $dialog.dialog('close');
            }
        });

        // open jquery UI dialog
        $dialog.dialog({
            modal: true,
            resizable: true,
            closeOnEscape: false,
            title: rcmail.gettext((list.id ? 'editlist' : 'createlist'), 'tasklist'),
            close: function() {
                $dialog.dialog('destroy').hide();
            },
            buttons: buttons,
            minWidth: 400,
            width: 420
        }).show();
    }

    /**
     *
     */
    function list_delete(id)
    {
        var list = me.tasklists[id];
        if (list && !list.norename && confirm(rcmail.gettext(list.children ? 'deletelistconfirmrecursive' : 'deletelistconfirm', 'tasklist'))) {
            saving_lock = rcmail.set_busy(true, 'tasklist.savingdata');
            rcmail.http_post('tasklist', { action:'delete', l:{ id:list.id } });
            return true;
        }
        return false;
    }

    /**
     *
     */
    function list_remove(id)
    {
        var list = me.tasklists[id];
        if (list && list.removable) {
            destroy_list(list);
            rcmail.http_post('tasklist', { action:'subscribe', l:{ id:list.id, active:0, permanent:0, recursive:1 } });
        }
    }

    /**
     * Callback from server to finally remove the given list
     */
    function destroy_list(prop)
    {
        var li, delete_ids = [],
            list = me.tasklists[prop.id];

        // find sub-lists
        if (list && list.children) {
            for (var child_id in me.tasklists) {
                if (String(child_id).indexOf(prop.id) == 0)
                    delete_ids.push(child_id);
            }
        }
        else {
            delete_ids.push(prop.id);
        }

        // delete all subfolders in the list
        for (var i=0; i < delete_ids.length; i++) {
            id = delete_ids[i];
            list = me.tasklists[id];
            tasklists_widget.remove(id);

            if (list) {
                list.active = false;
                // delete me.tasklists[prop.id];
                unlock_saving();
                remove_tasks(list.id);
            }
            $("#taskedit-tasklist option[value='"+id+"']").remove();
        }
    }

    /**
     *
     */
    function insert_list(prop)
    {
        if (prop._reload) {
            rcmail.redirect(rcmail.url(''));
            return;
        }

        tasklists_widget.insert({
            id: prop.id,
            classes: [ prop.group || '' ],
            virtual: prop.virtual,
            html: prop.html
        }, prop.parent || null, prop.group);

        // flag as tasklist for drag & drop
        $(tasklists_widget.get_item(prop.id)).data('type', 'tasklist');

        delete prop.html;
        me.tasklists[prop.id] = prop;

        // append to list selector in task edit dialog, too (#2985)
        $('<option>').attr('value', prop.id).html(Q(prop.name)).appendTo('#taskedit-tasklist');
    }

    /**
     *
     */
    function update_list(prop)
    {
        var id = prop.oldid || prop.id,
            li = tasklists_widget.get_item(id);

        if (prop._reload) {
            rcmail.redirect(rcmail.url(''));
            return;
        }

        if (me.tasklists[id] && li) {
            delete me.tasklists[id];
            me.tasklists[prop.id] = prop;
            $(li).find('input').first().val(prop.id);
            $(li).find('.listname').first().html(Q(prop.name));
            tasklists_widget.update(id, { id:prop.id, html:li.children().first() });
        }
    }

    /**
     *
     */
    function list_showurl(id)
    {
        var list = me.tasklists[id];
        if (list && list.caldavurl) {
            $('div.showurldialog:ui-dialog').dialog('close');

            var $dialog = $('<div>').addClass('showurldialog').append('<p>'+rcmail.gettext('caldavurldescription', 'tasklist')+'</p>'),
                textbox = $('<textarea>').addClass('urlbox').css('width', '100%').attr('rows', 2).appendTo($dialog);

            $dialog.dialog({
                resizable: true,
                closeOnEscape: true,
                title: rcmail.gettext('showcaldavurl', 'tasklist'),
                close: function() {
                  $dialog.dialog("destroy").remove();
                },
                width: 520
            }).show();

            textbox.val(list.caldavurl).select();
        }
    }

    /**
     * Execute search
     */
    function quicksearch()
    {
        var q;
        if (rcmail.gui_objects.qsearchbox && (q = rcmail.gui_objects.qsearchbox.value)) {
            var id = 'search-'+q;
            var resources = [];

            for (var rid in me.tasklists) {
                if (me.tasklists[rid].active) {
                    resources.push(rid);
                }
            }
            id += '@'+resources.join(',');

            // ignore if query didn't change
            if (search_request == id)
                return;

            search_request = id;
            search_query = q;

            list_tasks();
        }
        else  // empty search input equals reset
            this.reset_search();
    }

    /**
     * Reset search and get back to normal listing
     */
    function reset_search()
    {
        $(rcmail.gui_objects.qsearchbox).val('');

        if (search_request) {
            search_request = search_query = null;
            list_tasks();
        }
    }


    // method to show the print dialog.
    function print_tasks()
    {
        var param = {}, active = active_lists();

        if (active.length) {
            param = {filter: filtermask, lists: active.join(','), q: search_query};
            rcmail.open_window(rcmail.url('print', param), true, true);
        }
    };


    /**** Utility functions ****/

    // same as str.split(delimiter) but it ignores delimiters within quoted strings
    var explode_quoted_string = function(str, delimiter)
    {
      var result = [],
        strlen = str.length,
        q, p, i, chr, last;

      for (q = p = i = 0; i < strlen; i++) {
        chr = str.charAt(i);
        if (chr == '"' && last != '\\') {
          q = !q;
        }
        else if (!q && chr == delimiter) {
          result.push(str.substring(p, i));
          p = i + 1;
        }
        last = chr;
      }

      result.push(str.substr(p));
      return result;
    };

    /**
     * Clear any text selection
     * (text is probably selected when double-clicking somewhere)
     */
    function clearSelection()
    {
        if (document.selection && document.selection.empty) {
            document.selection.empty() ;
        }
        else if (window.getSelection) {
            var sel = window.getSelection();
            if (sel && sel.removeAllRanges)
                sel.removeAllRanges();
        }
    }

    /**
     * Check whether the event target is a descentand of the given element
     */
    function target_overlaps(target, elem)
    {
        while (target.parentNode) {
            if (target.parentNode == elem)
                return true;
            target = target.parentNode;
        }
        return false;
    }

    /**
     *
     */
    function active_lists()
    {
        var active = [];
        for (var id in me.tasklists) {
            if (me.tasklists[id].active)
                active.push(id);
        }
        return active;
    }

    // resize and reposition (center) the dialog window
    this.dialog_resize = function(id, height, width)
    {
        var win = $(window), w = win.width(), h = win.height();
            $(id).dialog('option', { height: Math.min(h-20, height+130), width: Math.min(w-20, width+50) })
                .dialog('option', 'position', ['center', 'center']);  // only works in a separate call (!?)
    };

    /**
     * Enable/disable focusview mode for the given list
     */
    function set_focusview(id, shift)
    {
        var in_focus = $.inArray(id, focusview_lists) >= 0,
            li = $(tasklists_widget.get_item(id)).find('.tasklist').first();

        // remove list from focusview
        if (in_focus && shift && id !== null) {
            focusview_lists = $.grep(focusview_lists, function(list_id) { return list_id != id; });
        }
        else {
            if (!shift || id === null) {
                focusview_lists = [];

                // uncheck all active focusview icons
                tasklists_widget.container.find('div.focusview')
                    .removeClass('focusview')
                    .find('a.quickview').attr('aria-checked', 'false');
            }

            if (!in_focus && id !== null) {
                focusview_lists.push(id)
            }
        }

        focusview = focusview_lists.length > 0;

        // activate list if necessary
        if (focusview && !me.tasklists[id].active) {
            li.find('input[type=checkbox]').get(0).checked = true;
            me.tasklists[id].active = true;
            fetch_counts();
        }

        // update list
        list_tasks(null);

        if (focusview) {
            li[in_focus ? 'removeClass' : 'addClass']('focusview')
                .find('a.quickview').attr('aria-checked', in_focus ? 'false' : 'true');
            $('body').addClass('quickview-active');
        }
        else {
            $('body').removeClass('quickview-active');
        }
    }


    // init dialog by default
    init_taskedit();
}


// extend jQuery
// from http://james.padolsey.com/javascript/sorting-elements-with-jquery/
jQuery.fn.sortElements = (function(){
    var sort = [].sort;

    return function(comparator, getSortable) {
        getSortable = getSortable || function(){ return this };

        var last = null;
        return sort.call(this, comparator).each(function(i){
            // at this point the array is sorted, so we can just detach each one from wherever it is, and add it after the last
            var node = $(getSortable.call(this));
            var parent = node.parent();
            if (last) last.after(node);
            else      parent.prepend(node);
            last = node;
        });
    };
})();

// equivalent to $.unique() but working on arrays of strings
jQuery.unqiqueStrings = (function() {
    return function(arr) {
        var hash = {}, out = [];

        for (var i = 0; i < arr.length; i++) {
            hash[arr[i]] = 0;
        }
        for (var val in hash) {
            out.push(val);
        }

        return out;
    };
})();


/* tasklist plugin UI initialization */
var rctasks;
window.rcmail && rcmail.addEventListener('init', function(evt) {

  rctasks = new rcube_tasklist_ui($.extend(rcmail.env.tasklist_settings, rcmail.env.libcal_settings));

  // register button commands
  rcmail.register_command('newtask', function(){ rctasks.edit_task(null, 'new', {}); }, true);
  rcmail.register_command('print', function(){ rctasks.print_tasks(); }, true);

  rcmail.register_command('list-create', function(){ rctasks.list_edit_dialog(null); }, true);
  rcmail.register_command('list-edit', function(){ rctasks.list_edit_dialog(rctasks.selected_list); }, false);
  rcmail.register_command('list-delete', function(){ rctasks.list_delete(rctasks.selected_list); }, false);
  rcmail.register_command('list-remove', function(){ rctasks.list_remove(rctasks.selected_list); }, false);
  rcmail.register_command('list-showurl', function(){ rctasks.list_showurl(rctasks.selected_list); }, false);

  rcmail.register_command('search', function(){ rctasks.quicksearch(); }, true);
  rcmail.register_command('reset-search', function(){ rctasks.reset_search(); }, true);
  rcmail.register_command('expand-all', function(){ rctasks.expand_collapse(true); }, true);
  rcmail.register_command('collapse-all', function(){ rctasks.expand_collapse(false); }, true);

  rctasks.init();
});
