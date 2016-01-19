<%-- list of all pages and their rating --%>
<% if PageRaterList %>
<ul id="PageRaterList">
	<% loop PageRaterList %>
	<li>
		<% with Page %><a href="$Link">$MenuTitle</a><% end_with %>
		<% include PageRaterStars %>
	</li>
	<% end_loop %>
</ul>
<% end_if %>
