<?php
if(isset($db)) {
  $db->connect("localhost", "movie_db", 'df*gdf*gd*ff*gd*ff*gd*fg*dfg*');
  $db->db="movie_db";
  $db->create_database($db->db, false);
}
else {
  exit("Error!\n");
}
