<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"/>
    <title>Dashboard | BPSC Cadre Allocation System</title>
    <meta name="description" content=""/>

    @include('partials.header')

  </head>

  <body style="padding-top: 0px;">
    <!-- Content wrapper -->
    <div>
      <!-- Content -->

        {{ $slot }}

      <!-- / Content -->

    </div>
    <!-- Content wrapper -->


    @include('partials.footer')

  </body>
</html>
