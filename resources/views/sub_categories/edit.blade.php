<div class="text-center mb-4">
    <h3 class="mb-2">Edit Sub Category</h3>
    <p class="text-muted">Update sub category details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.sub-categories.update', $subCategory) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="parentCategory">Category <span class="text-danger">*</span></label>
            <select id="parentCategory" name="category_id" class="form-select">
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $subCategory->category_id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="subCategoryName">Name <span class="text-danger">*</span></label>
            <input type="text" id="subCategoryName" name="name"
                class="form-control" placeholder="Enter Sub Category Name"
                value="{{ $subCategory->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Description <span class="text-danger">*</span></label>
            <div class="quill-editor-wrapper">
                <div id="sub-description-editor">{!! $subCategory->description !!}</div>
                <textarea name="description" id="sub-description-textarea" class="d-none">{{ $subCategory->description }}</textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Additional Information <span class="text-danger">*</span></label>
            <div class="quill-editor-wrapper">
                <div id="sub-information-editor">{!! $subCategory->additional_information !!}</div>
                <textarea name="additional_information" id="sub-information-textarea" class="d-none">{{ $subCategory->additional_information }}</textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Product Highlights <span class="text-danger">*</span></label>
            <div class="quill-editor-wrapper">
                <div id="sub-highlights-editor">{!! $subCategory->product_highlights !!}</div>
                <textarea name="product_highlights" id="sub-highlights-textarea" class="d-none">{{ $subCategory->product_highlights }}</textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="subCategoryStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="subCategoryStatus" name="status" value="1"
                    {{ $subCategory->status == 1 ? 'checked' : '' }} />
                <label class="form-check-label" for="subCategoryStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Sub Category</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<style>
    #commonModal .ql-editor {
        min-height: 120px;
        max-height: 200px;
        overflow-y: auto;
    }
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.375rem !important;
        border-top-right-radius: 0.375rem !important;
        border-bottom-left-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.375rem !important;
        border-bottom-right-radius: 0.375rem !important;
        border-top-left-radius: 0 !important;
        border-top-right-radius: 0 !important;
    }
    .quill-editor-wrapper:not(:has(textarea.is-invalid)) .ql-container,
    .quill-editor-wrapper:not(:has(textarea.is-invalid)) .ql-toolbar {
        border-color: #dbdade !important;
    }
</style>

<script>
    (function () {
        function setupEditors() {
            if (typeof Quill === 'undefined') return;

            function syncQuill(quill, textareaSelector) {
                const $ta = $(textareaSelector);
                const text = quill.getText().trim();
                const html = text.length === 0 ? '' : quill.root.innerHTML;
                $ta.val(html);
                if (text.length > 0) {
                    $ta.removeClass('is-invalid');
                    const $wrapper = $ta.closest('.quill-editor-wrapper');
                    $wrapper.find('.invalid-feedback').text('').hide();
                    $wrapper.find('.ql-container, .ql-toolbar').attr('style', function(i, s) {
                        return (s || '').replace(/border-color[^;]+;?/gi, '');
                    });
                }
            }

            const descQuill = new Quill('#sub-description-editor', {
                theme: 'snow',
                placeholder: 'Enter description...'
            });
            const infoQuill = new Quill('#sub-information-editor', {
                theme: 'snow',
                placeholder: 'Enter additional information...'
            });
            const hlQuill = new Quill('#sub-highlights-editor', {
                theme: 'snow',
                placeholder: 'Enter product highlights...'
            });

            descQuill.on('text-change', function () {
                syncQuill(descQuill, '#sub-description-textarea');
            });
            infoQuill.on('text-change', function () {
                syncQuill(infoQuill, '#sub-information-textarea');
            });
            hlQuill.on('text-change', function () {
                syncQuill(hlQuill, '#sub-highlights-textarea');
            });

            $(descQuill.root).on('input keyup paste', function () {
                syncQuill(descQuill, '#sub-description-textarea');
            });
            $(infoQuill.root).on('input keyup paste', function () {
                syncQuill(infoQuill, '#sub-information-textarea');
            });
            $(hlQuill.root).on('input keyup paste', function () {
                syncQuill(hlQuill, '#sub-highlights-textarea');
            });

            function syncAll() {
                syncQuill(descQuill, '#sub-description-textarea');
                syncQuill(infoQuill, '#sub-information-textarea');
                syncQuill(hlQuill, '#sub-highlights-textarea');
            }

            $('#commonModalForm').on('submit', syncAll);
            $('#commonModalForm button[type="submit"]').on('click', syncAll);
        }

        if (typeof Quill === 'undefined') {
            if (!document.querySelector('link[href*="quill/editor.css"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = "{{ asset('assets/vendor/libs/quill/editor.css') }}";
                document.head.appendChild(link);
            }
            if (!document.querySelector('link[href*="quill/typography.css"]')) {
                const link2 = document.createElement('link');
                link2.rel = 'stylesheet';
                link2.href = "{{ asset('assets/vendor/libs/quill/typography.css') }}";
                document.head.appendChild(link2);
            }
            const script = document.createElement('script');
            script.src = "{{ asset('assets/vendor/libs/quill/quill.js') }}";
            script.onload = setupEditors;
            document.head.appendChild(script);
        } else {
            setupEditors();
        }
    })();
</script>
