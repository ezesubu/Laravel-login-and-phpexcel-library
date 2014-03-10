<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>Laravel Authentication Demo</title>
    {{ HTML::style('/css/style.css') }}
</head>
<body>
    <div id="container">
        <div id="nav">
                @if(Auth::check())
                	<a href="{{ URL::to('logout') }}">loguot</a>
                @else
                   <a href="{{ URL::to('login') }}">Login</a>
                @endif               
                @if(Auth::user())
                	@if(Auth::user()->id ==5 )
                		<a href="{{ URL::to('upload') }}">Subir Archivo</a>
                	@endif	
                @endif
        </div><!-- end nav -->

       

        @yield('content')
    </div><!-- end container -->
</body>
</html>