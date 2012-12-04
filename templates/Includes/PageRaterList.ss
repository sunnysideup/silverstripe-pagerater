<% if PageRaterList %>
<ul id="PageRaterList">
	<% control PageRaterList %>
	<li>
		<% include PageRaterStars %>
		<% control Page %><a href="$Link">$MenuTitle</a><% end_control %>
	</li>
	<% end_control %>
</ul>
<% end_if %>
