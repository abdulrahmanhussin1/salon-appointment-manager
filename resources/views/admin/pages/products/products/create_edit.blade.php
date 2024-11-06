@extends('admin.layouts.app')
@section('title')
    {{ isset($product) ? __('Edit Product ') : __('Create Product') }}
@endsection
@section('css')
    <link href="{{ asset('admin-assets/assets/vendor/choices/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-assets/assets/vendor/choices/theme.min.css') }}" type="text/css" rel="stylesheet"
        id="style-default">
    <style>
        .custom-avatar {
            display: inline-block;
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
        }

        .custom-avatar img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            transition: opacity 0.25s;
            display: block;
        }

        .custom-avatar .overlay {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .custom-avatar:hover img,
        .custom-avatar:hover .overlay {
            opacity: 1;
        }

        .custom-avatar .icon {
            color: #ffffff;
            font-size: 32px;
        }
    </style>
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Product">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('products.index') }}">{{ __('Products') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($product) }}">
            {{ isset($product) ? __('Edit :type', ['type' => $product->name]) : __('Create New Product') }}
        </x-breadcrumb-item>


    </x-breadcrumb>
    {{-- End breadcrumbs --}}
    <div class="container">
        <div class="card radius-15 border-lg-top-primary">
            <div class="card-title">
                <h4 class="m-3 mb-0">
                    {{ isset($product) ? __('Edit :type', ['type' => $product->name]) : __('Create New product') }}
                </h4>
            </div>
            @include('admin.layouts.alerts')
            <hr>
            <form method="POST"
                action="{{ isset($product) ? route('products.update', ['product' => $product]) : route('products.store') }}"
                enctype="multipart/form-data" id="productForm">
                @csrf
                @if (isset($product))
                    @method('PUT')
                @endif
                <div class="card-body">
                    <div class="col-lg-12">
                        <x-form-personal-image :src="isset($product) && isset($product->photo)
                            ? asset('storage/' . $product->photo)
                            : asset('admin-assets/assets/img/OIP.jpeg')" name="image" />
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <x-input type='text' :value="isset($product) ? $product->name : old('name')" label="Name" name='name'
                                placeholder='product Name' id="name" oninput="" required />
                        </div>
                        <div class="col-6">
                            <x-input type="text" value="{{ $product->code ?? old('code') }}" label="Code"
                                id="code" name='code' placeholder="Code"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>
                        <div class="col-6">
                            <x-input type="text" value="{{ $product->supplier_price ?? old('supplier_price') }}"
                                label="Purchasing price" id="supplier_price" name='supplier_price'
                                placeholder="Purchasing price" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>

                        <div class="col-6">
                            <x-input type="text" value="{{ $product->customer_price ?? old('customer_price') }}"
                                label="Selling price" id="customer_price" name='customer_price' placeholder="Selling price"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-6">
                            <x-form-select name="category_id" id="category_id" label='Category' required>
                                <option value="">{{ __('Select one Category') }}</option>
                                @foreach ($productCategories as $category)
                                    <option @if (isset($product) && ($product->category_id == $category->id || old('category_id') == $category->id)) selected="selected" @endif
                                        value="{{  $category->id }}">{{  $category->name }}</option>
                                @endforeach
                            </x-form-select>
                        </div>

                        <div class="col-6">
                            <x-form-select name="supplier_id" id="supplier_id" label='Supplier' required>
                                <option value="">{{ __('Select one Supplier') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option @if (isset($product) && ($product->supplier_id == $supplier->id || old('supplier_id') == $supplier->id)) selected="selected" @endif
                                        value="{{  $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </x-form-select>
                        </div>

                        <div class="col-6">
                            <x-form-select name="unit_id" id="unit_id" label='unit' required>
                                <option value="">{{ __('Select one unit') }}</option>
                                @foreach ($units as $unit)
                                    <option @if (isset($product) && ($product->unit_id == $unit->id || old('unit_id') == $unit->id)) selected="selected" @endif
                                        value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </x-form-select>
                        </div>


                        <div class="col-6">
                            <x-form-select name='status' id="status" label="status" required>
                                <option @if (isset($product) && $product->status == 'active') selected @endif value="active">
                                    {{ __('Active') }}</option>
                                <option @if (isset($product) && $product->status == 'inactive') selected @endif value="inactive">
                                    {{ __('Inactive') }}</option>
                            </x-form-select>
                        </div>


                    </div>
                    <div class="col-12">
                        <x-form-description value="{{ $product->description ?? old('description') }}" label="description"
                            name='description' placeholder='Product description' />

                    </div>
                </div>
                <div class="text-center mt-2">
                    <x-submit-button label='Confirm' />

                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function openFileInput() {
            document.getElementById('fileInput').click();
        }

        function handleFileSelect() {
            const fileInput = document.getElementById('fileInput');
            const avatarImage = document.getElementById('avatar');

            const selectedFile = fileInput.files[0];

            if (selectedFile) {

                const reader = new FileReader();

                reader.onload = function(e) {
                    avatarImage.src = e.target.result;
                };

                reader.readAsDataURL(selectedFile);
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#productForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 255,
                    },
                    code: {
                        required: true,
                        digits: true,
                    },
                    description: {
                        maxlength: 500
                    },
                    category_id: {
                        required: false
                    },
                    supplier_id: {
                        required: false
                    },
                    unit_id: {
                        required: false
                    },
                    supplier_price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    customer_price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    status: {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: "Product name is required.",
                        maxlength: "Product name should not exceed 255 characters.",
                    },
                    code: {
                        required: "Product code is required.",
                        digits: "Product code must be an integer.",
                    },
                    image: {
                        extension: "Only JPEG, PNG, JPG, or GIF images are allowed.",
                        maxsize: "Image size must not exceed 2 MB."
                    },
                    supplier_price: {
                        required: "Supplier price is required.",
                        number: "Supplier price must be a number.",
                        min: "Supplier price cannot be negative."
                    },
                    customer_price: {
                        required: "Customer price is required.",
                        number: "Customer price must be a number.",
                        min: "Customer price cannot be negative."
                    },
                    status: {
                        required: "Status is required.",
                    }
                },
                errorClass: "error text-danger fs--1",
                errorElement: "span",
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass(errorClass).removeClass(validClass);
                    $(element.form).find("label[for=" + element.id + "]").addClass(errorClass);
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass(errorClass).addClass(validClass);
                    $(element.form).find("label[for=" + element.id + "]").removeClass(errorClass);
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });
        });
    </script>
@endsection
