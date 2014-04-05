<% if PageHasBeenRatedByUser %>
<div class="pageRaterStars alreadyRated">
<% with CurrentUserRating %>
	<label class="starLabel">Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_with %>
</div>
<% else %>
<div class="pageRaterStars notRatedYet">
<% loop PageRatingResults %>
	<label class="starLabel">Average Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_loop %>
</div>
<% end_if %>
