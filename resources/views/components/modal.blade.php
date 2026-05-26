<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered {{ $size ?? '' }}">
    <div class="modal-content p-3 p-md-5">
      <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h3 class="mb-2">{{ $title }}</h3>
          @isset($subtitle)
            <p class="text-muted">{{ $subtitle }}</p>
          @endisset
        </div>
        {{ $slot }}
      </div>
    </div>
  </div>
</div>
