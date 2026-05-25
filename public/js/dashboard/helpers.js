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
                LmsHelpers.notify('error', data.message || 'بيانات غير صحيحة');
                return;
            }
            LmsHelpers.notify('error', data?.message || 'حدث خطأ غير متوقع');
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
            return new FormData(form);
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
            $select.find('option:not(:first)').remove();
            (items || []).forEach(function (item) {
                const value = item[valueKey];
                const label = typeof labelFn === 'function' ? labelFn(item) : item[labelFn];
                $select.append('<option value="' + value + '">' + label + '</option>');
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

            const requestParams = resolveParams(params.page || 1);
            options.params = requestParams;

            this.showLoading(tableBodySelector);

            return apiCall(requestParams).then(function (res) {
                const items = Array.isArray(res.data) ? res.data : [];
                const meta = res.meta || {};

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

                const $table = LmsHelpers.resolveTableElement(tableBodySelector, tableSelector);
                if ($table.length && typeof initDataTable === 'function') {
                    initDataTable($table[0]);
                }

                LmsHelpers.renderPagination(meta, paginationSelector, function (page) {
                    options.params = resolveParams(page);
                    LmsHelpers.loadPaginatedTable(options);
                });
                return res;
            }).catch(function (error) {
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
            $(inputSelector).on('keyup', debounced);
        },
    };
})();
