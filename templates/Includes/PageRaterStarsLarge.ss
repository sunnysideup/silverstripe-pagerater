<% if PageHasBeenRatedByUser %>
<div class="pageRaterStars alreadyRated">
<% control CurrentUserRating %>
	<label class="starLabel">Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_control %>
</div>
<% else %>
<div class="pageRaterStars notRatedYet">
<% control PageRatingResults %>
	<label class="starLabel">Average Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_control %>
</div>
<% end_if %>
