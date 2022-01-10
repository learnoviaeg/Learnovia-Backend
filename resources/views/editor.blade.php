

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset=UTF-8>
    <title>This page is running in standards mode!</title>
  </head>
  <body>
    <textarea id="open-source-plugins">
     </textarea>
  </body>
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
  <!-- <script src="{{ asset('js/scripteditor.js') }}"> -->
    
  </script>
     <script > tinymce.init({
       @foreach($objEditor as $key => $val)
        @if(($key == 'external_plugins'))
          @foreach($objEditor['external_plugins'] as $key2 => $value)
          {{$key}} : { "{{$key2}}":"{{$value}}"},
            @continue;
          @endforeach
        @else
          {{$key}}:"{{$val}}",
        @endif
       @endforeach
     })
    </script>
</html>






