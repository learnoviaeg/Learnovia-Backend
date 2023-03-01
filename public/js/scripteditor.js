

var useDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
tinymce.init({
  // selector: 'textarea#open-source-plugins',
//   plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
//   // imagetools_cors_hosts: ['picsum.photos'],
//   menubar: 'file edit view insert format tools table help',
//   toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
//   toolbar_sticky: true,
//   autosave_ask_before_unload: true,
//   autosave_interval: '30s',
//   autosave_prefix: '{path}{query}-{id}-',
//   autosave_restore_when_empty: false,
//   autosave_retention: '2m',
//   image_advtab: true,
//   external_plugins: {
//     'tiny_mce_wiris': 'https://www.wiris.net/demo/plugins/tiny_mce/plugin.js'
//   },
//   importcss_append: true,
  // file_picker_callback: function (callback, value, meta) {
  //   /* Provide file and text for the link dialog */
  //   if (meta.filetype === 'file') {
  //     callback('https://www.google.com/logos/google.jpg', { text: 'My text' });
  //   }

  //   /* Provide image and alt text for the image dialog */
  //   if (meta.filetype === 'image') {
  //     callback('https://www.google.com/logos/google.jpg', { alt: 'My alt text' });
  //   }

  //   /* Provide alternative source and posted for the media dialog */
  //   if (meta.filetype === 'media') {
  //     callback('movie.mp4', { source2: 'alt.ogg', poster: 'https://www.google.com/logos/google.jpg' });
  //   }
  // },
  // content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px;height:100% }'
//
//
    // F:\learnovia\Learanovia-new\new-front\node_modules
    // selector: 'textarea#open-source-plugins',
    // external_plugins: {
    //   'tiny_mce_wiris': 'https://www.wiris.net/demo/plugins/tiny_mce/plugin.js',
    // },
    // plugins: [
    //   'preview',
    //   'autoresize',
    // ],
    // content_style: "body { margin: 0px;} p { margin: 0;direction: ltr;unicode-bidi: plaintext;  } img{max-width:400px;max-height:250px;}",
    // toolbar: false,
    // menubar:false,
    // statusbar: false,

    // menubar: 'view',
    // toolbar: 'preview',

    // strict_loading_mode: true,
    // setup: (editor)=> {
    //   editor.on('init', (e)=> {
    //     this.quizService.editorView.next(e);
    //   });
    // }
 });
