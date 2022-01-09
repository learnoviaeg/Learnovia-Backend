

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
  <script src="{{ asset('js/scripteditor.js') }}">
    
  </script>
     <script > tinymce.init({
       @foreach($objEditor as $key => $val)
         {{$key}}:"{{$val}}",
       @endforeach
     })
    </script>
</html>






