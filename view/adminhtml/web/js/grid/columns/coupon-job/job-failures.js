define([
    'Magento_Ui/js/grid/columns/column',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/translate'
], function (Column, modal, $) {
    'use strict';

    return Column.extend({

        defaults: {
            failedImportsCount: 0,
            failedMessagesCount: 0,
            failuresUrl: '',
            pageSize: 10
        },

        /**
         * Read authoritative failure counts from the job details.
         *
         * The counts live in the job_details blob (failed_imports keyed by batch,
         * failed_messages as a single total) so they remain correct even if the
         * per-job log files are deleted.
         *
         * @param {{ id: string, job_details: string }} rows
         */
        processRows: function (rows) {
            var job_details = JSON.parse(rows.job_details) || {},
                failedImports = job_details.failed_imports || {},
                failedMessages = job_details.failed_messages || {};

            this.failedImportsCount = Object.keys(failedImports).reduce(function (sum, key) {
                return sum + (Number((failedImports[key] || {})) || 0);
            }, 0);
            this.failedMessagesCount = Number(failedMessages) || 0;
        },

        /** @returns {boolean} */
        hasFailedImports: function () {
            return this.failedImportsCount > 0;
        },

        /** @returns {boolean} */
        hasFailedMessages: function () {
            return this.failedMessagesCount > 0;
        },

        /**
         * Open the failed-imports modal for a row.
         * @param {{id: string}} row
         */
        viewImports: function (row) {
            this._openModal(row.id, 'imports', $.mage.__('Failed Imports'), [
                { key: 'email',        label: $.mage.__('Email') },
                { key: 'failure_code', label: $.mage.__('Failure Code') },
                { key: 'description',  label: $.mage.__('Description') },
                { key: 'failed_at',           label: $.mage.__('Recorded At') }
            ]);
        },

        /**
         * Open the consumer-failures modal for a row.
         * @param {{id: string}} row
         */
        viewMessages: function (row) {
            this._openModal(row.id, 'messages', $.mage.__('Consumer Failures'), [
                { key: 'id',            label: $.mage.__('Message ID') },
                { key: 'error_message', label: $.mage.__('Error Message') },
                { key: 'failed_at',     label: $.mage.__('Recorded At') }
            ]);
        },

        /**
         * Core: open a Magento modal with a paged data-grid table inside it.
         *
         * Uses the native Magento admin data-grid and pager CSS that is already
         * loaded on every admin page — no custom CSS required.
         *
         * @param {string|number} jobId
         * @param {string}        category   'imports' | 'messages'
         * @param {string}        title
         * @param {Array.<{key:string, label:string}>} cols
         */
        _openModal: function (jobId, category, title, cols) {
            var self = this;

            /* ── Build structure ─────────────────────────────────────── */
            var $wrap  = $('<div class="ddg-failure-modal-wrap admin__data-grid-wrap"/>');
            var $table = $('<table class="data-grid data-grid-draggable"/>');
            var $head  = $('<thead><tr/></thead>');
            var $body  = $('<tbody/>');
            var $foot  = $('<div class="admin__data-grid-pager-wrap"/>');
            var $info  = $('<span class="ddg-pager-info"/>');
            var $prev  = $('<button type="button" class="action-previous"><span>' + $.mage.__('Previous') + '</span></button>');
            var $next  = $('<button type="button" class="action-next"><span>' + $.mage.__('Next') + '</span></button>');

            cols.forEach(function (c) {
                $head.find('tr').append('<th class="data-grid-th _sortable"><span>' + c.label + '</span></th>');
            });
            $table.append($head).append($body);
            $foot.append($prev).append($info).append($next);
            $wrap.append($table).append($foot);

            /* ── State ───────────────────────────────────────────────── */
            var state = { page: 1, total: 0, loading: false };

            function render(items) {
                $body.empty();
                if (!items.length) {
                    $body.append(
                        '<tr><td colspan="' + cols.length + '" class="empty-text">'
                        + $.mage.__('No failures recorded.') + '</td></tr>'
                    );
                    return;
                }
                items.forEach(function (item) {
                    var $tr = $('<tr class="data-row"/>');
                    cols.forEach(function (c) {
                        $tr.append('<td>' + $('<span/>').text(item[c.key] != null ? item[c.key] : '').html() + '</td>');
                    });
                    $body.append($tr);
                });
            }

            function updatePager() {
                var totalPages = Math.max(1, Math.ceil(state.total / self.pageSize));
                $info.text(
                    $.mage.__('Page %1 of %2 (%3 total)')
                        .replace('%1', state.page)
                        .replace('%2', totalPages)
                        .replace('%3', state.total)
                );
                $prev.prop('disabled', state.page <= 1);
                $next.prop('disabled', state.page >= totalPages);
            }

            function load(page) {
                if (state.loading) { return; }
                state.loading = true;
                $wrap.addClass('_loading');
                $.ajax({
                    url: self.failuresUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: { id: jobId, category: category, page: page, pageSize: self.pageSize }
                }).done(function (resp) {
                    state.page  = page;
                    state.total = Number(resp.totalRecords) || 0;
                    render(resp.items || []);
                    updatePager();
                }).fail(function () {
                    render([]);
                    updatePager();
                }).always(function () {
                    state.loading = false;
                    $wrap.removeClass('_loading');
                });
            }

            $prev.on('click', function () { if (state.page > 1) { load(state.page - 1); } });
            $next.on('click', function () { load(state.page + 1); });

            /* ── Open modal ──────────────────────────────────────────── */
            var $modal = $wrap.modal({
                type: 'slide',
                title: title,
                modalClass: 'ddg-coupon-job-failures-modal',
                buttons: []
            });

            $modal.modal('openModal');
            load(1);
        }
    });
});
