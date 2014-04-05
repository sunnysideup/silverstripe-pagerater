<% if PageRaterList %>
<ul id="PageRaterList">
	<% loop PageRaterList %>
	<li>
		<% include PageRaterStars %>
		<% with Page %><a href="$Link">$MenuTitle</a><% end_with %>
	</li>
	<% end_loop %>
</ul>
<% end_if %>
