<div class="text-center mb-4">
    <h3 class="mb-2">Edit Attribute</h3>
    <p class="text-muted">Update attribute details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.attributes.update', $attribute) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="attributeName">Name <span class="text-danger">*</span></label>
            <input type="text" id="attributeName" name="name"
                class="form-control" placeholder="Enter Attribute Name"
                value="{{ $attribute->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Values <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <input type="text" id="valueInput" class="form-control" placeholder="Enter Attribute Value" />
                <button type="button" id="addValueBtn" class="btn btn-outline-primary text-nowrap">Add</button>
            </div>
            <div class="mt-2">
                <table class="table table-bordered table-sm mb-0" id="valuesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Value</th>
                            <th style="width:60px">Action</th>
                        </tr>
                    </thead>
                    <tbody id="valuesTableBody"></tbody>
                </table>
            </div>
            <input type="hidden" id="valuesHidden" name="values_json" value="" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="attributeStatus" name="status" value="1"
                    {{ $attribute->status == 1 ? 'checked' : '' }} />
                <label class="form-check-label" for="attributeStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Attribute</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
$(document).ready(function () {
    let values = [];
    let editIndex = null;

    function renderValues() {
        const $tbody = $('#valuesTableBody');
        $tbody.empty();
        values.forEach(function (item, idx) {
            $tbody.append(
                '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + $('<span>').text(item.value).html() + '</td>' +
                    '<td><div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-icon text-primary edit-value" data-index="' + idx + '"><i class="ti ti-pencil"></i></button><button type="button" class="btn btn-sm btn-icon text-danger remove-value" data-index="' + idx + '"><i class="ti ti-trash"></i></button></div></td>' +
                '</tr>'
            );
        });
        $('#valuesHidden').val(JSON.stringify(values));
    }

    function resetAddInput() {
        $('#valueInput').val('').focus();
        $('#addValueBtn').text('Add').removeClass('btn-warning').addClass('btn-outline-primary');
        editIndex = null;
    }

    @foreach($attribute->values as $v)
        values.push({ value: {!! json_encode($v->value) !!} });
    @endforeach
    renderValues();

    $('#addValueBtn').on('click', function () {
        const $input = $('#valueInput');
        const val = $input.val().trim();
        if (!val) return;

        if (editIndex !== null) {
            const exists = values.some(function (item, i) { return i !== editIndex && item.value.toLowerCase() === val.toLowerCase(); });
            if (exists) {
                alert('This value already exists.');
                return;
            }
            values[editIndex].value = val;
            renderValues();
            resetAddInput();
        } else {
            const exists = values.some(function (item) { return item.value.toLowerCase() === val.toLowerCase(); });
            if (!exists) {
                values.push({ value: val });
                renderValues();
                $input.val('').focus();
            }
        }
    });

    $('#valueInput').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#addValueBtn').trigger('click');
        }
    });

    $('#valuesTable').on('click', '.remove-value', function () {
        const idx = $(this).data('index');
        if (typeof idx === 'undefined' || values[idx] === undefined) return;
        if (editIndex === idx) resetAddInput();
        values.splice(idx, 1);
        renderValues();
    });

    $('#valuesTable').on('click', '.edit-value', function () {
        const idx = $(this).data('index');
        if (typeof idx === 'undefined' || values[idx] === undefined) return;
        $('#valueInput').val(values[idx].value).focus();
        $('#addValueBtn').text('Update').removeClass('btn-outline-primary').addClass('btn-warning');
        editIndex = idx;
    });

    $('#commonModalForm').on('submit', function () {
        if (values.length === 0) {
            $('#valuesHidden').after('<div class="text-danger small mt-1">Please add at least one value.</div>');
            return false;
        }
    });
});
</script>
