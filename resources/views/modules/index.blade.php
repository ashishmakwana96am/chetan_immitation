@extends('layouts.app')

@section('title', 'Modules')

@section('page-css')
    <style>
        .module-category-row {
            user-select: none;
            background-color: #f8f8f8;
        }
        .module-category-row:hover {
            background-color: #f0f2f5;
        }
        .module-category-row td {
            font-weight: 600;
            color: #566a7f;
            padding: 12px 16px;
        }
        .module-child-row td {
            padding: 10px 16px;
        }
        .module-child-row.d-none {
            display: none !important;
        }
        .toggle-icon {
            transition: transform 0.2s ease;
            display: inline-block;
        }
        .toggle-icon.open {
            transform: rotate(90deg);
        }
        .category-name-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .drag-handle {
            cursor: grab;
            color: #adb5bd;
            padding: 0 4px;
        }
        .drag-handle:hover { color: #566a7f; }
        .drag-handle:active { cursor: grabbing; }
        tr.sortable-ghost {
            opacity: 0.4;
            background: #e7e3ff !important;
        }
        tr.sortable-chosen {
            background: #f0eeff !important;
            box-shadow: 0 2px 8px rgba(180,119,30,0.15);
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Modules List</h4>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0" id="modulesTable">
                <thead class="table-light">
                    <tr>
                        @can('reorder modules') <th style="width:36px"></th> @endcan
                        <th style="width:5%">#</th>
                        <th>Name</th>
                        <th>Icon</th>
                        <th>Route Name</th>
                        <th>Permission</th>
                        <th style="width:8%">Sort</th>
                    </tr>
                </thead>
                <tbody id="categoriesSortable">
                    @php $catIndex = 0; @endphp
                    @foreach($modules as $parent)
                        @php $catIndex++; @endphp

                        {{-- Category Row --}}
                        <tr class="module-category-row"
                            data-category="{{ $parent->id }}"
                            data-id="{{ $parent->id }}"
                            data-sort="{{ $parent->sort_order }}">
                            @can('reorder modules')
                                <td class="drag-handle" title="Drag to reorder"><i class="ti ti-grip-vertical"></i></td>
                            @endcan
                            <td>{{ $catIndex }}</td>
                            <td>
                                <div class="category-name-cell" style="cursor:pointer;" onclick="toggleCategory({{ $parent->id }}, this)">
                                    <i class="ti ti-chevron-right toggle-icon {{ $parent->children->count() > 0 ? '' : 'invisible' }}"></i>
                                    <i class="ti ti-folder text-warning"></i>
                                    <span>{{ $parent->name }}</span>
                                    @if($parent->children->count() > 0)
                                        <span class="badge bg-label-primary">{{ $parent->children->count() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td><span class="text-muted">-</span></td>
                            <td><span class="text-muted">-</span></td>
                            <td><span class="text-muted">-</span></td>
                            <td><span class="badge bg-label-success sort-badge">{{ $parent->sort_order }}</span></td>
                        </tr>

                        {{-- Child Rows --}}
                        @if($parent->children->count() > 0)
                            <tr class="children-container-row d-none" data-parent-container="{{ $parent->id }}">
                                <td colspan="{{ auth()->user()->can('reorder modules') ? 7 : 6 }}" class="p-0">
                                    <table class="table mb-0 w-100">
                                        <tbody class="children-sortable" data-parent-id="{{ $parent->id }}">
                                            @foreach($parent->children as $child)
                                                <tr class="module-child-row"
                                                    data-id="{{ $child->id }}"
                                                    data-sort="{{ $child->sort_order }}">
                                                    @can('reorder modules')
                                                        <td style="width:36px" class="drag-handle ps-4" title="Drag to reorder"><i class="ti ti-grip-vertical"></i></td>
                                                    @endcan
                                                    <td style="width:5%"></td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2 ps-3">
                                                            <i class="ti ti-corner-down-right text-muted" style="font-size:0.85rem;"></i>
                                                            @if($child->icon)
                                                                <i class="{{ $child->icon }} text-primary"></i>
                                                            @endif
                                                            <span>{{ $child->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($child->icon)
                                                            <code><i class="{{ $child->icon }} me-1"></i>{{ $child->icon }}</code>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($child->route)
                                                            <code>{{ $child->route }}</code>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($child->permission)
                                                            <code>{{ $child->permission }}</code>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><span class="badge bg-label-success sort-badge">{{ $child->sort_order }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    @can('reorder modules')
    <script src="{{ asset('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
    <script>
        const reorderUrl = '{{ route('admin.modules.reorder') }}';
        const csrfToken  = '{{ csrf_token() }}';

        function saveOrder(orderData) {
            $.ajax({
                url    : reorderUrl,
                type   : 'POST',
                data   : JSON.stringify({ order: orderData, _token: csrfToken }),
                contentType: 'application/json',
                success: function (res) {
                    if (res.status === 'success') {
                        toastr.success('Order saved successfully.');
                    }
                },
                error: function () {
                    toastr.error('Failed to save order.');
                }
            });
        }

        // ── Category-level drag & drop ───────────────────────────────
        Sortable.create(document.getElementById('categoriesSortable'), {
            handle      : '.module-category-row .drag-handle',
            animation   : 150,
            ghostClass  : 'sortable-ghost',
            chosenClass : 'sortable-chosen',
            filter      : '.children-container-row',
            onMove      : function (evt) {
                if (evt.related && evt.related.classList.contains('children-container-row')) {
                    return false;
                }
            },
            onEnd: function () {
                const orderData = [];
                let i = 1;
                $('#categoriesSortable > tr.module-category-row').each(function () {
                    const id = $(this).data('id');
                    $(this).find('td').eq(1).text(i);
                    $(this).find('.sort-badge').first().text(i);
                    orderData.push({ id: id, sort_order: i });

                    const container = $('[data-parent-container="' + id + '"]').closest('tr');
                    if (container.length) {
                        $(this).after(container);
                    }
                    i++;
                });
                saveOrder(orderData);
            }
        });

        // ── Children-level drag & drop (per parent) ──────────────────
        document.querySelectorAll('.children-sortable').forEach(function (el) {
            Sortable.create(el, {
                handle      : '.drag-handle',
                animation   : 150,
                ghostClass  : 'sortable-ghost',
                chosenClass : 'sortable-chosen',
                onEnd: function () {
                    const orderData = [];
                    let i = 1;
                    $(el).find('tr.module-child-row').each(function () {
                        const id = $(this).data('id');
                        $(this).find('.sort-badge').text(i);
                        orderData.push({ id: id, sort_order: i });
                        i++;
                    });
                    saveOrder(orderData);
                }
            });
        });
    </script>
    @endcan

    <script>
        function toggleCategory(categoryId, nameCell) {
            const container = $('[data-parent-container="' + categoryId + '"]');
            const icon      = $(nameCell).find('.toggle-icon');

            if (container.hasClass('d-none')) {
                container.removeClass('d-none');
                icon.addClass('open');
            } else {
                container.addClass('d-none');
                icon.removeClass('open');
            }
        }
    </script>
@endsection
