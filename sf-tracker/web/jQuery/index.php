<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

     

<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

<script src="src/jquery.picture.cut.js"></script> 

   <div id="container_image"></div>   
   
   <script >
	  $("#container_image").PictureCut({
	  InputOfImageDirectory       : "image",
	  PluginFolderOnServer        : "/jQuery/",
	  FolderOnServer              : "/uploads/",
	  EnableCrop                  : true,
	  CropWindowStyle             : "Bootstrap"
  });
   </script> 