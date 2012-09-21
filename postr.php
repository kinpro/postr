<?php
session_start();
include('includes/header.php');
if(empty($_SESSION['uid'])){
header('Location: index.php');
}
?>
	<body>
		<div class="container">
			<span class="logout"><a id="logout" href="#"><?php echo $_SESSION['email']; ?> [Logout]</a></span>
			<div class="app_title">
				<img src="img/postr.png"/>
				<h2>Postr</h2>
			</div>
			
			<div class="form_container">
				<form class="custom">
					<label for="status">What's Up?</label>
					<textarea name="status" id="status" style="height:100px;">
					</textarea>
					
					<a href="#" id="post_status" class="success button">Post</a>
					<a href="#" id="settings">Settings</a>
					
				</form> 
			</div><!--/.form_container-->
			
			
		</div><!--/.container-->
		
		<div id="settings_modal" class="reveal-modal medium">
			<h3>Settings</h3>
			<span>Where to Post?</span>
			<p>
				<form id="settings_form">
					<p>
						<label data-for="facebook">
							<input type="checkbox" id="facebook">
							<a href="#" class="network_settings">Facebook</a>
						</label>
						
					</p>
					<p>
						<label data-for="gplus">
							<input type="checkbox" id="gplus" >
							<a href="#" class="network_settings">Google+</a>
						</label>
					</p>
					<p>
						<label data-for="linked_in">
							<input type="checkbox" id="linked_in">
							<a href="#" class="network_settings">LinkedIn</a>
						</label>
					</p>
					<p>
						<label data-for="twitter">
							<input type="checkbox" id="twitter">
							<a href="#" class="network_settings">Twitter</a>
						</label>
					</p>
				</form>
			</p>
			<a class="close-reveal-modal">&#215;</a>
		</div><!--/#settings_modal-->
		
		<div id="facebook_modal" class="reveal-modal medium">
			<h3>Facebook Settings</h3>
			<p>
				<label for="fb_pages">Pages</label>
				<input type="text" id="fb_pages"/>
			</p>
			<p>
				<div id="current_fb_pages">
					
				</div>
			</p>
			<p>
				<a href="#" id="add_fb_page" class="success button">Add Page</a>
			</p>
			<a class="close-reveal-modal">&#215;</a>
		</div><!--/#facebook_modal-->
		
		<div id="fb-root"></div>
	</body>
	
	
	
<?php
include('includes/footer.php');
?>	
	<script src="http://connect.facebook.net/en_US/all.js"></script>
	<script src="libs/foundation/javascripts/jquery.foundation.reveal.js"></script>
	<script src="libs/foundation/javascripts/jquery.foundation.forms.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
	
	<script>
		var users = new Store("users");
		var current_users = {};
		var current_user = {};
		var current_fb_page = {};
		
		$(document).foundationCustomForms();
		
		$('#status').val("");
		
		
		$.post(
			'actions/actions.php',
			{'action' : 'get_uid'},
			function(data){
				current_user.uid = data;
				current_user.settings = users.get('users')[current_user.uid]['settings'];
				
				var index = 0;
				for(var x in current_user.settings){
					
					var network = x;
					var network_status = current_user.settings[x]['status'];
					
					if(network_status != 0){
						$($('#settings_form input[type=checkbox]')[index]).attr('checked', true);
						
						switch(network){
							case 'facebook':
								fb_login();
							break;
						}
						
					}
					index++;
				}
				
				var fb_pages = current_user.settings.facebook.pages;
				var fb_pages_container = $('#current_fb_pages');
				
				
				for(var x in fb_pages){
					var page_id = x;
					
					var fb_page = $("<div>");
					var page_img = $("<img>").attr("src", fb_pages[page_id]['page_img']);
					var page_name = $("<span>").text(fb_pages[page_id]['page_name']);
					var page_status = fb_pages[page_id]['page_status'];
					
					var page_checkbox = $("<input>").attr({
						"type" : "checkbox", 
						"id" : page_id, 
						"class" : "current_fb_pages",
						"checked" : !!page_status
					});
					
					
					page_img.appendTo(fb_page);
					page_checkbox.appendTo(fb_page);
					page_name.appendTo(fb_page);
				}
				
				fb_pages_container.append(fb_page);
			}
		);
		
		if(!users.get('users')){
			//set users if it doesn't exists yet
			users.set('users', {});
		}else{
			current_users = users.get('users');
		}
	
		$('.network_settings').live('click', function(e){
			e.preventDefault();
			$('#facebook_modal').reveal();
		});
		
		$('#settings').click(function(e){
			e.preventDefault();
			$('#settings_modal').reveal();
		});
	
		$('#logout').click(function(e){
			e.preventDefault();
			$.post(
				'actions/actions.php', 
				{'action' : 'logout'}, 
				function(){
					window.location.replace('index.php');
				}
			);
		});
		
		$('#post_status').click(function(e){
			e.preventDefault();
			fb_post();
		});
		
		$('#settings_form input[type=checkbox]').click(function(){
			
			var network = $(this).attr('id');
			var status = Number(!!$(this).attr('checked'));
			
			current_user['settings'][network] = {};
			current_user['settings'][network]['status'] = status;
			current_users[current_user.uid]['settings'][network]['status'] = status;
			users.set('users', current_users);
			
		});
		
		
		/*facebook*/
		FB.init({appId: "355248497890497", status: true, cookie: true});
		
		var fb_login = function(){
			FB.login(
				function(response){
					FB.api({
					  method : 'fql.multiquery',
					  queries: {
						'q1' : 'SELECT page_id FROM page_admin WHERE uid = me()',
						'q2' : 'SELECT page_id, name, pic_small, description FROM page WHERE page_id IN (SELECT page_id FROM #q1)'
					  }
					}, 
						function(data){
						
							var user_pages = data[1]['fql_result_set'];
							var data_source = [];
							for(var x in user_pages){
								var page_obj = user_pages[x];
								
								var page_id = page_obj['page_id'];
								var page_name = page_obj['name'];
								var page_description = page_obj['description'];
								var page_pic = page_obj['pic_small']
								
								data_source.push(
									{
									'value' : page_name, 'page_name' : page_name, 
									'page_id' : page_id, 'page_pic' : page_pic,
									'page_description' : page_description
									} 
								);
								
							}
							
							$('#fb_pages').autocomplete({
								source: data_source,
								select: function(event, ui){
									current_fb_page['page_id'] = ui['item']['page_id'];
									current_fb_page['page_description'] = ui['item']['page_description'];
									current_fb_page['page_name'] = ui['item']['page_name'];
									current_fb_page['page_pic'] = ui['item']['page_pic'];
								}
							}).data("autocomplete")._renderItem = function(ul, item){
								return $("<li></li>")
								.data("item.autocomplete", item)
								.append("<a id='"+  item.page_id +"'>" + "<img src='" + item.page_pic + "' />" + item.page_name+ "</a>" )
								.appendTo( ul );
							};
							
							
						}
					);
				}, 
				{scope: 'user_about_me,email,read_friendlists,publish_stream,manage_pages'}
			);
		
		};
		
		$('#add_fb_page').click(function(e){
			e.preventDefault();
			
			if(!!!current_user.settings.facebook.pages[current_fb_page['page_id']]){
				$('#fb_pages').val('');
				
				var current_fb_pages = $('#current_fb_pages');
				var fb_page = $("<div>");
				
				var page_img = $("<img>").attr("src", current_fb_page['page_pic']);
				var page_name = $("<span>").text(current_fb_page['page_name']);
				var page_checkbox = $("<input>").attr({
					"type" : "checkbox", 
					"id" : current_fb_page['page_id'], 
					"class" : "current_fb_pages",
					"checked" : true
				});
				
				fb_page.append(page_img);
				fb_page.append(page_checkbox);
				fb_page.append(page_name);
				
				current_fb_pages.append(fb_page);
				
				current_user['settings']['facebook']['pages'] = {};
				current_user['settings']['facebook']['pages'][current_fb_page['page_id']] = {
					"page_name" : current_fb_page['page_name'], 
					"page_img" : current_fb_page['page_pic']
				};
				
				current_users[current_user.uid]['settings']['facebook']['pages'] = {};
				current_users[current_user.uid]['settings']['facebook']['pages'][current_fb_page['page_id']] = {
					"page_name" : current_fb_page['page_name'], 
					"page_img" : current_fb_page['page_pic'],
					"page_status" : 1
				};
				users.set('users', current_users);
				
				noty_success.text = 'Facebook Page Successfully Added!';
				noty(noty_success);
			}else{
				noty_err.text = 'The selected Facebook Page has already been added before!';
				noty(noty_err);
			}
		});
		
		$('.current_fb_pages').live('click', function(){
			//change status whether to post to the currently selected facebook page or not
			var page_id = $(this).attr('id');
			var page_status = Number(!!$(this).attr('checked'));
			current_user['settings']['facebook']['pages'][page_id]['page_status'] = page_status; 
			
			current_users[current_user.uid]['settings']['facebook']['pages'][page_id]['page_status'] = page_status;
			users.set('users', current_users);
		});
		
		var fb_post = function(){
			var post_contents = {
				message : 'Testing message with images and links',
				name : 'test test',
				link : 'http://google.com',
				description : 'test post to facebook page'
			};

			FB.api('/217828178231935/feed', 'post', post_contents, 
				function(response){
					if(!response || response.error){
						noty_err.text = 'Facebook Post Unsuccessful';
						noty(noty_err);
					}else{
						
					}
				}
			);
		};
		
	</script>
</html>
 