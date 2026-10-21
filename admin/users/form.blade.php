<div class="row mb-4">
    <label for="profile_image" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Name</label>
    <div class="col-sm-9">
        <input class="form-control" type="text" id="name" name="name" value="{{@$data->name}}" placeholder="Please enter name">
    </div>
</div>
<div class="row mb-4">
    <label for="email" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Email</label>
    <div class="col-sm-9">
        <input type="email" class="form-control" id="email" name="email" value="{{@$data->email}}" placeholder="Please enter email">
    </div>
</div>
