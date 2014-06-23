<!-- no need for CSRF protection, cross-posting is a feature here -->
<form name="postform" method="post" action="<?=htmlspecialchars(BASEURL, ENT_QUOTES)?>">
	<h1>
	Enter the text you want to share 
	</h1>
	<h2>
	(<?=htmlspecialchars($config['paste_max_chars']/1024)?>k chars max)
	</h2>
	<textarea name="content" rows="30" cols="82" ></textarea><br/>
	Time to live: <select name="ttl">
	<option value="3600">1 hour</option>
	<option value="14400">4 hours</option>
	<option value="86400" selected="selected">1 day</option>
	<option value="604800">1 week</option>
	<option value="2592000">30 days</option>
	</select>
	<br/>
	<input value="Save" type="submit"/>
	<div class="text">
	Hint: put the following oneliner in your .bashrc to use "6p &lt;file&gt;" or "echo hi | 6p" or "cat &lt;&lt; EOF | 6p" (thanks, Habbie and Reinhart):<br /><br />
	<code>6p() { curl -s -F "content=&lt;${1--}" -F ttl=604800 -w "%{redirect_url}\n" -o /dev/null <?=htmlspecialchars($config['server_name'])?>; }</code>
	</div>
</form>
