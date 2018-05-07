<%-- inner for ratings ... LARGE --%>
<% if PageHasBeenRatedByUser %>
<div class="pageRaterStars alreadyRated userRating">
<% loop CurrentUserRating %>
    <label class="starLabel">Your Rating:</label>
    <div class="stars">
        <div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
        <img alt="$Stars stars" src="pagerater/images/stars.png" />
    </div>
<% end_loop %>
<p class="rateAgain"><a href="$Link(rateagain)#Form_PageRatingForm">rate again</a></p>
</div>
<% end_if %>
<% if PageRatingResults %>
<div class="pageRaterStars  alreadyRated averageRating">
<% loop PageRatingResults %>
    <label class="starLabel">Average Rating:</label>
    <div class="stars">
        <div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
        <img alt="$Stars stars" src="pagerater/images/stars.png" />
    </div>
<% end_loop %>
</div>
<% end_if %>
