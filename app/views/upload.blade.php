<h2>Agregar Torneo</h2>
 	@if (Session::has('flash_error'))
        <div id="flash_error">{{ Session::get('flash_error') }}</div>
    @endif
{{Form::open(array( 'url '=>'upload', 'files' => 'true', 'enctype' => "multipart/form-data"))}}

    {{Form::label('csv', 'file')}}

    {{Form::file('csv')}}

    {{Form::submit('Subir')}}

{{Form::close()}}