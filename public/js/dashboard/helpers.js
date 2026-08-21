(function () {
    'use strict';

    const roleLabels = {
        ADMIN: 'مدير',
        LIBRARIAN: 'أمين مكتبة',
        MEMBER: 'عضو',
    };

    window.LmsHelpers = {
        notify(status, message, title) {
            const content = {
                message: message,
                title: title || 'إشعار',
                icon: status === 'success' ? 'fa fa-check' : 'fa fa-times',
            };

            if (typeof $.notify === 'function') {
                const notification = $.notify(content, {
                    type: status === 'success' ? 'success' : 'danger',
                    placement: { from: 'top', align: 'right' },
                    time: 2000,
                    delay: 0,
                });
                setTimeout(function () {
                    notification.close();
                }, 5000);
            } else {
                alert(message);
            }
        },

        responseMessage(data, fallback) {
            if (!data) return fallback || 'تمت العملية بنجاح';
            if (data.body) return data.body;
            if (data.message && data.message !== 'success' && data.message !== 'error') {
                return data.message;
            }
            return fallback || 'تمت العملية بنجاح';
        },

        getStoredUser() {
            try {
                return JSON.parse(localStorage.getItem(LmsApi.USER_KEY) || 'null');
            } catch (e) {
                return null;
            }
        },

        setStoredUser(user) {
            localStorage.setItem(LmsApi.USER_KEY, JSON.stringify(user));
        },

        clearAuth() {
            localStorage.removeItem(LmsApi.TOKEN_KEY);
            localStorage.removeItem(LmsApi.USER_KEY);
        },

        saveAuth(token, user) {
            localStorage.setItem(LmsApi.TOKEN_KEY, token);
            this.setStoredUser(user);
        },

        isStaff(user) {
            return user && (user.role === 'ADMIN' || user.role === 'LIBRARIAN');
        },

        roleLabel(role) {
            return roleLabels[role] || role;
        },

        updateHeaderUser(user) {
            if (!user) return;
            $('#headerUserName, #dropdownUserName').text(user.full_name || user.email);
            $('#dropdownUserEmail').text(user.email || '');
            $('#dropdownUserRole').text(this.roleLabel(user.role));
            if (user.photo_url) {
                $('#headerAvatar, #dropdownAvatar').attr('src', user.photo_url);
            }
        },

        applyRoleSidebar(user) {
            if (!user) return;
            $('[data-role]').each(function () {
                const roles = ($(this).data('role') || '').toString().split(',');
                if (roles.indexOf(user.role) === -1) {
                    $(this).hide();
                }
            });
        },

        showLoading(selector) {
            $(selector).html('<tr><td colspan="20" class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</td></tr>');
        },

        showEmpty(selector, message) {
            $(selector).html('<tr><td colspan="20" class="empty-state">' + (message || 'لا توجد بيانات') + '</td></tr>');
        },

        renderPagination(meta, containerSelector, onPage) {
            const $container = $(containerSelector);
            $container.empty();
            if (!meta || meta.last_page <= 1) return;

            let html = '<nav><ul class="pagination justify-content-center">';
            for (let i = 1; i <= meta.last_page; i++) {
                html += '<li class="page-item ' + (i === meta.current_page ? 'active' : '') + '">';
                html += '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            }
            html += '</ul></nav>';
            $container.html(html);
            $container.find('.page-link').on('click', function (e) {
                e.preventDefault();
                onPage(parseInt($(this).data('page'), 10));
            });
        },

        clearFormErrors(formSelector) {
            $(formSelector).find('.is-invalid').removeClass('is-invalid');
            $(formSelector).find('.invalid-feedback').text('');
        },

        showFormErrors(formSelector, errors) {
            this.clearFormErrors(formSelector);
            if (!errors) return;

            Object.keys(errors).forEach(function (key) {
                const field = $(formSelector).find('[name="' + key + '"]');
                const message = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                field.addClass('is-invalid');
                field.closest('.form-group').find('.invalid-feedback').text(message);
            });
        },

        handleApiError(error, formSelector) {
            const data = error.response?.data;
            if (error.response?.status === 422 && data?.errors) {
                if (formSelector) {
                    LmsHelpers.showFormErrors(formSelector, data.errors);
                }
                LmsHelpers.notify('error', data.body || data.message || 'بيانات غير صحيحة');
                return;
            }
            LmsHelpers.notify('error', data?.body || data?.message || 'حدث خطأ غير متوقع');
        },

        instanceStateLabel(state) {
            const map = {
                available: 'متاح',
                borrowed: 'مستعار',
                reserved: 'محجوز',
                damaged: 'تالف',
                lost: 'مفقود',
            };
            return map[state] || state;
        },

        orderStateLabel(state) {
            const map = {
                pending: 'قيد الانتظار',
                confirmed: 'مؤكد',
                rejected: 'مرفوض',
                cancelled: 'ملغى',
            };
            return map[state] || state;
        },

        memberStateLabel(state) {
            const map = {
                ACTIVE: 'نشط',
                PAUSED: 'موقوف',
                CANCLED: 'ملغى',
            };
            return map[state] || state;
        },

        memberStateBadge(state) {
            const map = {
                ACTIVE: 'bg-success',
                PAUSED: 'bg-warning text-dark',
                CANCLED: 'bg-danger',
            };
            const label = LmsHelpers.memberStateLabel(state);
            const badgeClass = map[state] || 'bg-secondary';
            return '<span class="badge ' + badgeClass + '">' + label + '</span>';
        },

        buildListParams(page, config) {
            config = config || {};
            const params = { page: page || 1 };

            if (config.extra && typeof config.extra === 'object') {
                Object.assign(params, config.extra);
            }

            if (config.search) {
                const query = String($(config.search).val() || '').trim();
                if (query) {
                    params.search = query;
                }
            }

            Object.keys(config.fields || {}).forEach(function (paramKey) {
                const selector = config.fields[paramKey];
                const value = $(selector).val();
                if (value !== '' && value !== null && value !== undefined) {
                    params[paramKey] = value;
                }
            });

            return params;
        },

        bindTableFilters(config, reloadFn) {
            const reload = function () {
                reloadFn(1);
            };

            if (config.search) {
                this.setupSearch(config.search, reload);
            }

            const fieldSelectors = Object.values(config.fields || {}).filter(Boolean);
            if (fieldSelectors.length) {
                $(fieldSelectors.join(',')).on('change', reload);
            }
        },

        formToObject(form) {
            const formData = new FormData(form);
            const data = {};
            formData.forEach(function (value, key) {
                data[key] = value;
            });
            return data;
        },

        formToFormData(form) {
            const formData = new FormData(form);
            Array.from(formData.entries()).forEach(function (entry) {
                const key = entry[0];
                const value = entry[1];
                if (value instanceof File && !value.name && value.size === 0) {
                    formData.delete(key);
                }
            });
            return formData;
        },

        afterFormSave(res, options) {
            options = options || {};
            LmsHelpers.notify('success', LmsHelpers.responseMessage(res, options.fallback));
            if (!options.isEdit && options.indexUrl) {
                setTimeout(function () {
                    window.location.href = options.indexUrl;
                }, 400);
            }
        },

        extractItems(res) {
            if (Array.isArray(res?.data)) return res.data;
            if (Array.isArray(res?.data?.data)) return res.data.data;
            return [];
        },

        extractMeta(res, items) {
            return res?.meta || res?.data?.meta || {
                current_page: 1,
                last_page: 1,
                per_page: (items || []).length || 15,
                total: (items || []).length,
            };
        },

        destroyDataTable(table) {
            const $table = table && table.jquery ? table : $(table);
            if (!$table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($table[0])) {
                return;
            }
            $table.DataTable().destroy();
            $table.removeClass('dataTable no-footer');
        },

        guessFileType(accept, url, file) {
            const source = ((accept || '') + ' ' + (file?.type || '') + ' ' + (url || '') + ' ' + (file?.name || '')).toLowerCase();
            if (source.indexOf('image') !== -1 || /\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/.test(source)) {
                return 'image';
            }
            if (source.indexOf('pdf') !== -1 || /\.pdf(\?|$)/.test(source)) {
                return 'pdf';
            }
            if (source.indexOf('audio') !== -1 || /\.(mp3|wav|ogg|m4a|aac)(\?|$)/.test(source)) {
                return 'audio';
            }
            return 'file';
        },

        previewButtonLabel(type) {
            if (type === 'image') return 'عرض الصورة';
            if (type === 'pdf') return 'عرض الملف';
            if (type === 'audio') return 'عرض الملف';
            return 'عرض الملف';
        },

        showFilePreview(options) {
            options = options || {};
            const file = options.file;
            const url = options.url;
            if (!file && !url) {
                LmsHelpers.notify('error', 'لا يوجد ملف للعرض');
                return;
            }

            const type = options.type || this.guessFileType('', url, file);
            const objectUrl = file ? URL.createObjectURL(file) : url;
            const $modal = $('#lmsFilePreviewModal');
            const $body = $('#lmsFilePreviewBody');
            const $title = $('#lmsFilePreviewTitle');

            if (!$modal.length) {
                window.open(objectUrl, '_blank');
                return;
            }

            $title.text(options.title || this.previewButtonLabel(type));
            $body.empty();

            if (type === 'image') {
                $body.html('<img src="' + objectUrl + '" alt="معاينة" class="img-fluid rounded">');
            } else if (type === 'pdf') {
                $body.html('<iframe src="' + objectUrl + '" title="معاينة الملف" style="width:100%;min-height:70vh;border:0;"></iframe>');
            } else if (type === 'audio') {
                $body.html('<audio controls src="' + objectUrl + '" class="w-100"></audio>');
            } else {
                $body.html('<a href="' + objectUrl + '" target="_blank" rel="noopener" class="btn btn-primary">فتح الملف</a>');
            }

            const modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            $modal.off('hidden.bs.modal.lmsPreview').on('hidden.bs.modal.lmsPreview', function () {
                if (file) {
                    URL.revokeObjectURL(objectUrl);
                }
            });
            modal.show();
        },

        enhanceFileInputs(root) {
            const scope = root ? $(root) : $(document);
            scope.find('input[type="file"]').each(function () {
                const input = this;
                if (input.dataset.previewReady === '1') return;
                input.dataset.previewReady = '1';

                const $input = $(input);
                if (!$input.closest('.input-group').length) {
                    $input.wrap('<div class="input-group lms-file-input-group"></div>');
                }

                const type = LmsHelpers.guessFileType(input.accept, input.dataset.currentUrl);
                const $group = $input.closest('.input-group');
                if (!$group.find('.btn-preview-file').length) {
                    $group.append(
                        '<button type="button" class="btn btn-outline-secondary btn-preview-file">' +
                        LmsHelpers.previewButtonLabel(type) +
                        '</button>'
                    );
                }

                $group.find('.btn-preview-file').off('click.lmsPreview').on('click.lmsPreview', function () {
                    const currentType = LmsHelpers.guessFileType(input.accept, input.dataset.currentUrl, input.files?.[0]);
                    LmsHelpers.showFilePreview({
                        type: currentType,
                        file: input.files?.[0] || null,
                        url: input.dataset.currentUrl || '',
                    });
                });
            });
        },

        setFileCurrentUrl(selector, url) {
            const input = $(selector)[0];
            if (!input) return;
            if (url) {
                input.dataset.currentUrl = url;
            } else {
                delete input.dataset.currentUrl;
            }
        },

        getChoicesInstance(selector) {
            const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
            return el && el.choicesInstance ? el.choicesInstance : null;
        },

        initChoices(selector, extraConfig) {
            const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
            if (!el || typeof Choices === 'undefined') return null;

            if (el.choicesInstance) {
                el.choicesInstance.destroy();
                el.choicesInstance = null;
            }

            const instance = new Choices(el, Object.assign({
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                searchPlaceholderValue: 'ابحث...',
                noResultsText: 'لا توجد نتائج',
                noChoicesText: 'لا توجد خيارات',
                removeItemButton: !!el.multiple,
                allowHTML: false,
            }, extraConfig || {}));

            el.choicesInstance = instance;
            return instance;
        },

        enhanceSelects(root) {
            const scope = root ? $(root) : $(document);
            scope.find('select').each(function () {
                if (this.dataset.remote === '1' || this.dataset.choices === '0' || this.dataset.previewReady === '1') {
                    return;
                }
                if (this.choicesInstance) return;
                LmsHelpers.initChoices(this);
            });
        },

        initRemoteSelect(selector, options) {
            options = options || {};
            const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
            if (!el) return null;
            el.dataset.remote = '1';

            const placeholder = options.placeholder || ($(el).find('option:first').text() || 'اختر');
            const choices = this.initChoices(el, {
                searchPlaceholderValue: 'ابحث...',
                noResultsText: 'لا توجد نتائج',
            });

            const mapItems = function (items) {
                const mapped = [{
                    value: '',
                    label: placeholder,
                    selected: !options.selectedValue,
                    disabled: !el.multiple,
                }];
                (items || []).forEach(function (item) {
                    const value = String(typeof options.valueKey === 'function' ? options.valueKey(item) : item[options.valueKey]);
                    mapped.push({
                        value: value,
                        label: typeof options.labelFn === 'function' ? options.labelFn(item) : item[options.labelFn],
                        selected: options.selectedValue !== undefined && options.selectedValue !== null
                            && String(options.selectedValue) === value,
                    });
                });
                return mapped;
            };

            const load = function (query) {
                return options.fetchFn(query || '').then(function (items) {
                    if (!choices) {
                        LmsHelpers.fillSelect(el, items, options.valueKey, options.labelFn, options.selectedValue);
                        return items;
                    }
                    choices.setChoices(mapItems(items), 'value', 'label', true);
                    return items;
                });
            };

            load(options.initialQuery || '');

            if (el && choices) {
                el.addEventListener('search', LmsHelpers.debounce(function (event) {
                    load(event.detail?.value || '');
                }, 350));
            }

            return { choices: choices, reload: load };
        },

        debounce(fn, delay) {
            let timer;
            return function () {
                const args = arguments;
                const context = this;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fn.apply(context, args);
                }, delay || 400);
            };
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return dateStr;
        },

        conditionLabel(condition) {
            const map = {
                new: 'جديد',
                worn: 'مستعمل',
                almost_new: 'شبه جديد',
            };
            return map[condition] || condition;
        },

        fillSelect(selector, items, valueKey, labelFn, selectedValue) {
            const $select = $(selector);
            const el = $select[0];
            const firstLabel = $select.find('option:first').text() || 'اختر';
            const resolveValue = function (item) {
                return typeof valueKey === 'function' ? valueKey(item) : item[valueKey];
            };
            const resolveLabel = function (item) {
                return typeof labelFn === 'function' ? labelFn(item) : item[labelFn];
            };

            if (el && el.choicesInstance) {
                const mapped = [{
                    value: '',
                    label: firstLabel,
                    selected: selectedValue === undefined || selectedValue === null || selectedValue === '',
                }];
                (items || []).forEach(function (item) {
                    const value = String(resolveValue(item));
                    mapped.push({
                        value: value,
                        label: resolveLabel(item),
                        selected: selectedValue !== undefined && selectedValue !== null && String(selectedValue) === value,
                    });
                });
                el.choicesInstance.setChoices(mapped, 'value', 'label', true);
                if (selectedValue !== undefined && selectedValue !== null && selectedValue !== '') {
                    el.choicesInstance.setChoiceByValue(String(selectedValue));
                }
                return;
            }

            $select.find('option:not(:first)').remove();
            (items || []).forEach(function (item) {
                $select.append('<option value="' + resolveValue(item) + '">' + resolveLabel(item) + '</option>');
            });
            if (selectedValue !== undefined && selectedValue !== null) {
                $select.val(String(selectedValue));
            }
        },

        resolveTableElement(tableBodySelector, tableSelector) {
            if (tableSelector && tableSelector !== '.table-datatable') {
                const $bySelector = $(tableSelector);
                if ($bySelector.length) return $bySelector;
            }
            return $(tableBodySelector).closest('table');
        },

        whenAuthReady(callback) {
            if (window.LmsAuthReady && typeof window.LmsAuthReady.then === 'function') {
                return window.LmsAuthReady.then(callback);
            }
            return Promise.resolve(callback());
        },

        loadPaginatedTable(options) {
            const {
                apiCall,
                tableBodySelector,
                paginationSelector,
                totalSelector,
                renderRow,
                params = {},
                tableSelector,
                getParams,
            } = options;

            const resolveParams = function (page) {
                if (typeof getParams === 'function') {
                    return getParams(page);
                }
                const nextParams = Object.assign({}, options.params || params);
                nextParams.page = page || 1;
                return nextParams;
            };

            const requestId = (options._requestId || 0) + 1;
            options._requestId = requestId;

            const requestParams = resolveParams((options.params && options.params.page) || params.page || 1);
            options.params = requestParams;

            const $table = this.resolveTableElement(tableBodySelector, tableSelector);
            this.destroyDataTable($table);
            this.showLoading(tableBodySelector);

            return apiCall(requestParams).then(function (res) {
                if (options._requestId !== requestId) {
                    return res;
                }

                const items = LmsHelpers.extractItems(res);
                const meta = LmsHelpers.extractMeta(res, items);

                if (totalSelector) {
                    $(totalSelector).text('العدد: ' + (meta.total || items.length));
                }

                if (!items.length) {
                    LmsHelpers.showEmpty(tableBodySelector);
                    if (paginationSelector) {
                        $(paginationSelector).empty();
                    }
                    return res;
                }

                let html = '';
                items.forEach(function (item, index) {
                    html += renderRow(item, index, meta);
                });
                $(tableBodySelector).html(html);

                LmsHelpers.renderPagination(meta, paginationSelector, function (page) {
                    options.params = resolveParams(page);
                    LmsHelpers.loadPaginatedTable(options);
                });
                return res;
            }).catch(function (error) {
                if (options._requestId !== requestId) {
                    return;
                }
                if (error && error.response) {
                    LmsHelpers.handleApiError(error);
                } else if (error) {
                    console.error(error);
                    LmsHelpers.notify('error', 'حدث خطأ أثناء عرض البيانات');
                }
                LmsHelpers.showEmpty(tableBodySelector, 'تعذر تحميل البيانات');
            });
        },

        setupSearch(inputSelector, callback) {
            const debounced = this.debounce(function () {
                callback($(inputSelector).val());
            }, 400);
            $(inputSelector).on('keyup input search', debounced);
        },

        resolveButton(target) {
            if (!target) return null;
            if (target.jquery) return target[0] || null;
            if (target.tagName === 'FORM') {
                return target.querySelector('[type="submit"]');
            }
            return target.closest ? (target.closest('button, .btn, [type="submit"]') || target) : target;
        },

        busyLabelFor(button) {
            const text = ((button && (button.dataset.originalText || button.textContent)) || '').replace(/\s+/g, ' ').trim();
            if (text.includes('إرسال')) return 'جاري الإرسال...';
            if (text.includes('تحديث') || text.includes('تعديل')) return 'جاري التحديث...';
            if (text.includes('توليد')) return 'جاري التوليد...';
            if (text.includes('دفع')) return 'جاري الدفع...';
            if (text.includes('شحن')) return 'جاري الشحن...';
            if (text.includes('دخول') || text.includes('تسجيل')) return 'جاري تسجيل الدخول...';
            return 'جاري الحفظ...';
        },

        lockButton(target) {
            const button = this.resolveButton(target);
            if (!button) return null;
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
                button.dataset.originalText = (button.textContent || '').replace(/\s+/g, ' ').trim();
            }
            button.dataset.busy = '1';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + this.busyLabelFor(button);
            return button;
        },

        unlockButton(target) {
            const button = this.resolveButton(target);
            if (!button) return;
            button.dataset.busy = '0';
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
        },

        isBusy(target) {
            const button = this.resolveButton(target);
            return !!(button && button.dataset.busy === '1');
        },

        withBusy(target, work) {
            const button = this.resolveButton(target);
            if (this.isBusy(button)) {
                return Promise.resolve();
            }
            this.lockButton(button);
            return Promise.resolve()
                .then(work)
                .catch(function () {})
                .finally(function () {
                    LmsHelpers.unlockButton(button);
                });
        },

        bindBusyForm(form, handler) {
            if (!form) return;
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const button = event.submitter || form.querySelector('[type="submit"]');
                if (LmsHelpers.isBusy(button)) {
                    return;
                }
                LmsHelpers.lockButton(button);
                Promise.resolve(handler.call(form, event, form))
                    .catch(function () {})
                    .finally(function () {
                        if (document.body.contains(button)) {
                            LmsHelpers.unlockButton(button);
                        }
                    });
            });
        },
    };

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        const button = event.submitter || form.querySelector('[type="submit"]');
        if (button && button.dataset.busy === '1') {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
})();
