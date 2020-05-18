@if ($errors->any())
        @foreach ($errors->all() as $error)
        <div class="alert alert-danger p-1 mt-1 mt-3 error-msg" role="alert">
            {{ $error }}
            <button type="button" class="close mr-2" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endforeach
@endif
