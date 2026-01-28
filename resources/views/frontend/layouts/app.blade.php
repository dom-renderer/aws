<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  	<link rel="icon" type="image/x-icon" href="{{ Helper::favicon() }}">

      <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/front.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/animate.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">

      @stack('css')
   </head>
   <body>
    @include('frontend.layouts.header')

    @include('frontend.layouts.side-cart')

    <div class="main-wrappper">
        @yield('content')
    </div>

    @include('frontend.layouts.footer')
    @include('frontend.layouts.script')

    @stack('js')
   </body>
</html>