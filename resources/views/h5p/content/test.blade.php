<script>
    function loadFrame(){
        document.getElementById('myIframe').contentWindow.postMessage('test','*');
        console.log("loaded");
      }

      window.addEventListener('message',function(message){
    alert(message.data);
    });
    </script>

<iframe id="myIframe" src="http://127.0.0.1:8000/api/h5p/create" 
         
height="600" width="100%" onload="loadFrame()"></iframe>
<h1> Hello </h1>
