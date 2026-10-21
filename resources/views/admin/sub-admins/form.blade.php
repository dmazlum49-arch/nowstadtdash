<div class="row mb-4">
    <label for="name" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Name</label>
    <div class="col-sm-9">
        <input class="form-control" type="text" id="name" name="name" value="{{@$data->name}}" placeholder="Please enter name" required>
    </div>
</div>
<div class="row mb-4">
    <label for="email" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Email</label>
    <div class="col-sm-9">
        <input type="email" class="form-control" id="email" name="email" value="{{@$data->email}}" placeholder="Please enter email" required>
    </div>
</div>
<div class="row mb-4">
    <label for="password" class="col-sm-3 col-form-label">{{ !isset($data) ? '*' : '' }}Password</label>
    <div class="col-sm-9">
        <input type="password" class="form-control" id="password" name="password" placeholder="Please enter password" {{ !isset($data) ? 'required' : '' }}>
        @if(isset($data))
            <small class="text-muted">Leave blank to keep current password.</small>
        @endif
    </div>
</div>
<div class="row mb-4">
    <label for="password_confirmation" class="col-sm-3 col-form-label">{{ !isset($data) ? '*' : '' }}Confirm Password</label>
    <div class="col-sm-9">
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm password" {{ !isset($data) ? 'required' : '' }}>
    </div>
</div>
<div class="row mb-4">
    <label for="profile_image" class="col-sm-3 col-form-label">Profile Image</label>
    <div class="col-sm-9">
        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
        @if(isset($data) && $data->profile_image)
            <div class="mt-2 text-center" style="width: 100px;">
                {!! get_fancy_box_html($data->profile_image) !!}
            </div>
        @endif
    </div>
</div>
