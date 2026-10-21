@extends('layouts.admin.app')

@section('head_css')
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="main_form" action="{{ route('admin.credentials') }}" method="POST" enctype="multipart/form-data" >
                                @csrf
                                <div id="repeater-container">
                                    <input type="button" class="btn btn-success mb-3 mt-lg-0" id="add-repeater" value="Add"/>
                                    @forelse($fields as $field)
                                    <div class="repeater-list">
                                        <div class="row repeater-item">
                                            <input type="hidden" name="credential[id][]" value="{{ $field['id'] ?? '' }}" />
                                            <div class="mb-3 col-lg-5">
                                                <label for="">Key</label>
                                                <input type="text" name="credential[key][]" class="form-control" value="{{ $field['key'] }}"/>
                                            </div>
                                            <div class="mb-3 col-lg-5">
                                                <label for="">Value</label>
                                                <input type="text" name="credential[value][]" class="form-control" value="{{ $field['value'] }}"/>
                                            </div>
                                            <div class="col-lg-2 align-self-center">
                                                <div class="d-grid">
                                                    <label for=""></label>
                                                    <input type="button" class="btn btn-primary delete-repeater" value="Delete"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="repeater-list">
                                        <div class="row repeater-item">
                                            <div class="mb-3 col-lg-5">
                                                <label for="">Key</label>
                                                <input type="text" name="credential[key][]" class="form-control" value=""/>
                                            </div>
                                            <div class="mb-3 col-lg-5">
                                                <label for="">Value</label>
                                                <input type="text" name="credential[value][]" class="form-control" value=""/>
                                            </div>
                                            <div class="col-lg-2 align-self-center">
                                                <div class="d-grid">
                                                    <label for=""></label>
                                                    <input type="button" class="btn btn-primary delete-repeater" value="Delete"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                                <div class="row mt-3">
                                    <div class="col-sm-9">
                                        <div>
                                            <button id="submit-btn" class="btn btn-primary waves-effect waves-light" type="submit">
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const repeaterContainer = document.getElementById("repeater-container");
            const addRepeaterButton = document.getElementById("add-repeater");

            // Function to add a new repeater item
            addRepeaterButton.addEventListener("click", function () {
                const repeaterList = repeaterContainer.querySelector(".repeater-list"); // Select the repeater list
                if (!repeaterList) {
                    console.error("Repeater list not found.");
                    return;
                }

                const newItem = document.createElement("div");
                newItem.className = "row repeater-item";
                newItem.innerHTML = `
                    <div class="mb-3 col-lg-5">
                        <label for="">Key</label>
                        <input type="text" name="credential[key][]" class="form-control" />
                    </div>
                    <div class="mb-3 col-lg-5">
                        <label for="">Value</label>
                        <input type="text" name="credential[value][]" class="form-control" />
                    </div>
                    <div class="col-lg-2 align-self-center">
                        <div class="d-grid">
                            <label for=""></label>
                            <input type="button" class="btn btn-primary delete-repeater" value="Delete" />
                        </div>
                    </div>
                `;

                // Append the new item to the repeater list
                repeaterList.appendChild(newItem);

                // Add delete functionality to the new delete button
                newItem.querySelector(".delete-repeater").addEventListener("click", function () {
                    newItem.remove();
                });
            });

            // Attach delete functionality to the existing delete buttons
            repeaterContainer.addEventListener("click", function (event) {
                if (event.target.classList.contains("delete-repeater")) {
                    event.target.closest(".repeater-item").remove();
                }
            });
        });
    </script>
@endsection
