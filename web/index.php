<html lang="pt_BR">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
		<link rel="shortcut icon" href="favicon.ico" />
		<title>AIML 2.5 Web Test</title>
		<script src="js/jquery.js" type="text/javascript"></script>
		<link rel="stylesheet" href="css/main.css" type="text/css" />
		
		<script type="text/javascript">
			$(document).ready(function(){

				var localUrl = window.location.href;
				localUrl = localUrl.replace('web/', '');

				// só um advinhador simples
				var webServiceUrl 	= window.location.href.replace('web/', '');

				$('.esquecer').click(function(){

					Clear();
					AddText('Sistema', 'Esquecendo...');
					
					$('.userMessage').hide();

					$.ajax({
						  type: "GET",
						  url: webServiceUrl,
						  data: {
							  	requestType:'forget'
						  	},
						  success: function(response){
							  AddText('Sistema', 'Ok!');
							  $('.userMessage').show();
						  },
						  error: function(request, status, error)
						  {
							  Clear();
							  alert('error');
							  $('.userMessage').show();
						  }
						});
				});
				
				
				$('#fMessage').submit(function(){
					
					// get user input
					var userInput		= $('input[name="userInput"]').val();
					
					// basic check
					if(userInput == '')
						return false;
					//
					
					// clear
					$('input[name="userInput"]').val('');
					
					// hide button
					$(this).hide();
					
					// show user input
					AddText('Você', userInput);
					
					$.ajax({
					  type: "GET",
					  url: webServiceUrl,
					  data: {
						  	input:userInput,
						  	requestType:'talk'
					  	},
					  success: function(response){
						  AddText('Cenouro', response.message);
						  $('#fMessage').show();
						  $('input[name="userInput"]').focus();
					  },
					  error: function(request, status, error)
					  {
						  alert('error');
						  $('#fMessage').show();
					  }
					});

					return false;
				});

				function Clear()
				{
					$('.chatBox').html('');
				}
				
				function AddText(user, message)
				{
					console.log(user);
					console.log(message);

					var div 	= $('<div>');
					var name	= $('<labe>').addClass('name');
					var text	= $('<span>').addClass('message');
					
					name.text(user + ':');
					text.text('\t' + message);
					
					div.append(name);
					div.append(text);
					
					$('.chatBox').append(div);
					
					$('.chatBox').scrollTop($(".chatBox").scrollTop() + 100);
				}
				
				
				
				
			});
		</script>
	</head>
	<body>
		<div class="chatBox">
		
		</div>
		<label>
		<div class="userMessage">
			<form id="fMessage">
				<input type="text" name="userInput"/><input type="submit" value="Enviar" class="send"/>
			</form>
			<button class="esquecer">Esquecer</button>
		</div>
	</body>
</html>