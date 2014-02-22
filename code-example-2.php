<?php
/**
 * GRAPHClass - counts the volume of records per month over x months and displays it as a SVG.
 * PHP Version 5.0.0
 * Version 0.11
 * @package GRAPHClass
 * @link https://github.com/shortdark/gitme/
 * @author Neil Ludlow (shortdark) <neil@shortdark.net>
 * @copyright 2014 Neil Ludlow
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */


/**
 * GRAPHClass - counts the volume of records per month over x months and displays it as a SVG.
 * Version 0.12
 * @package GRAPHClass
 * @link https://github.com/shortdark/gitme/
 * @author Neil Ludlow (shortdark) <neil@shortdark.net>
 * @copyright 2014 Neil Ludlow
 */
class GRAPHClass 
{
	
/**
 * Specify how many months to go back from current month
 */ 
private $startingmonth= 72;

/**
 * Specify the scale of the y-axis
 */ 
private $yscale= 1;
/**
 * Specify the distance between points on the x-axis
 */ 
private $monthwidth = 10;

/**
 * Specify the length of the graph x axis in pixels
 */
private $axiswidth = 735;

/**
 * Specify the length of the graph y axis in pixels
 */
private $axisheight = 250;

/**
 * Specify the height of the whole SVG image
 */
private $graphheight = 260;

/**
 * Specify the width of the whole SVG image
 */
private $graphwidth = 795;

/**
 * Specify the colour of the main graph and points thereon
 */
private $main_graphline_color = "green";

/**
 * Specify the colour of the dashed "year average" graph and legend
 */
private $average_graphline_color = "red";
/**
 * Specify the colour of the axes and plain text
 */
private $axes_color = "black";

/**
 * Specify the associated website URL
 */
public $siteurl = "";

	
	/**
	 * Connect to MySQL via a new PDO
	 */
	public function __construct(){
		# Values changed
		$this->db = new PDO("mysql:host=localhost;dbname=XXXXXX;charset=utf8", "XXXXXX", "XXXXXX");
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}
	
	


	/**
	 * This is the main function that co-ordinates the gathering of data and displays it.
	 * 
	 * 
	 * @return string the SVG
	 */

	public function draw_chrono_records_svg(){
		
		# These two variables are used to give the monthly average
		$monthaggregator = 0;
		$count_months_recorded_this_year = 0;
				
		
		# loop for each month in the time range
		for($i=$this->startingmonth;$i>=0;$i--){
			
			# Get the numeric values of the month and year that was $i months ago
			$searchmonth = date("n")-$i;
			$searchyear = date("Y");
			if(0 >= $searchmonth){
				while(0 >= $searchmonth){
					# Count the number of years back we've gone and get month and year integers for
					# that point in time
					$searchmonth += 12;
					$searchyear--;
				}
			}
			
			# Get volume for this month
			$monthlyrecordvolume = intval($this->count_chrono_records($searchmonth,$searchyear));
			
			# Draw main graph line and dots (dots link to other pages)
			$main_line .= $this->draw_svg_main_line($i, $monthlyrecordvolume);
			$dots .= $this->draw_svg_main_dots($i, $monthlyrecordvolume);
			
			# Collect the month average data and draw the year-end lines
			$monthaggregator += $monthlyrecordvolume;
			$count_months_recorded_this_year++;
			
			# If the month is December or the for loop is at the end (i.e. $1=0 and it is this month) 
			# draw the year average line and reset $monthaggregator and $count_months_recorded_this_year 
			# to zero
			if(12 == $searchmonth or 0 == $i ){
				$ave_this_year = $monthaggregator / $count_months_recorded_this_year;
				$ave_lines .= $this->draw_svg_ave_lines($i, $ave_this_year, $searchyear);
				$monthaggregator = 0;
				$count_months_recorded_this_year = 0;
			}
		}
		
		# Display everything
		$this->display_svg_graph = "<h2>Records Per Month</h2>\n";
		$this->display_svg_graph .= "<svg class=\"graph\" width=\"" . $this->graphwidth . "px\" height=\"" . $this->graphheight . "px\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">\n";
		$this->display_svg_graph .= $this->draw_axes();
		$this->display_svg_graph .= $main_line;
		$this->display_svg_graph .= $ave_lines;
		$this->display_svg_graph .= $dots;
		$this->display_svg_graph .= "</svg>\n";
		return $this->display_svg_graph;
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * Gets the date timestamp for the month beginning and ending then gets
	 * the volume of records for that month
	 *
	 * @return integer volume of records
	 */

	private function count_chrono_records($searchmonth,$searchyear){
		$searchmonthnext = $searchmonth + 1;
		$start = mktime(0, 0, 0, $searchmonth, 1, $searchyear);
		$end = mktime(23, 59, 59, $searchmonthnext, 0, $searchyear);
		
		try {
  		  $stmt = $this->db->prepare(" SELECT COUNT(*) FROM massivetable WHERE recordadded >= :start AND recordadded <= :end ");
				$stmt->bindValue(':start', $start, PDO::PARAM_INT);
				$stmt->bindValue(':end', $end, PDO::PARAM_INT);
				$stmt->execute();
				$this->volume_of_records_in_timerange = $stmt->fetchColumn();
		} catch(PDOException $ex) {
		    $this->volume_of_records_in_timerange = "ERROR!!!";
		}
		
		return $this->volume_of_records_in_timerange;
	}
	
	
	
	
	
	/**
	 * Plots the main data on the graph joined up by a solid line
	 *
	 * @return string when the for loop completes in draw_chrono_records_svg(), this will have
	 * created the main graph plotted by the data points
	 */
	
	private function draw_svg_main_line($month, $monthlyrecordvolume){
		$month = intval($month);
		$monthlyrecordvolume = intval($monthlyrecordvolume);
		
		$yvalue = $this->axisheight - $monthlyrecordvolume;
		$xvalue = ($this->startingmonth-$month)*$this->monthwidth + $this->monthwidth;
		
		if($month == $this->startingmonth){
			$this->main_graph_line = "\t<path d=\"M$xvalue $yvalue ";
		}elseif(0 == $month){
			$this->main_graph_line = "L$xvalue $yvalue\" stroke-linejoin=\"round\" stroke=\"$this->main_graphline_color\" fill=\"none\"/>\n";
		}else{
			$this->main_graph_line = "L$xvalue $yvalue ";
		}
		
		return $this->main_graph_line;
	}
	
	
	
	
	
	
	
  /**
	 * Draws a dot on each point on the graph, the dots would link to the full list of 
	 * records
	 *
	 * @return string puts a dot at each of the data points of the main graph
	 */
	
	private function draw_svg_main_dots($month, $monthlyrecordvolume){
		$month = intval($month);
		$monthlyrecordvolume = intval($monthlyrecordvolume);
		
		$yvalue = $this->axisheight - $monthlyrecordvolume;
		$xvalue = ($this->startingmonth-$month)*$this->monthwidth + $this->monthwidth;
		
		$this->main_graph_dots = "<a xlink:href=\"#\" xlink:title=\"$monthlyrecordvolume\" ><circle cx=\"$xvalue\" cy=\"$yvalue\" r=\"2\" stroke=\"$this->main_graphline_color\" fill=\"$this->main_graphline_color\" stroke-width=\"1\" /></a>\n";
		
		return $this->main_graph_dots;
	}
	
	
	
	
	
	
	/**
	 * This method draws the dotted red lines that are the average volume per month,
	 * and write the average value in red above the line. Also tidies up by drawing the year 
	 * and a vertical dotted line to separate one year from another.
	 *
	 * @return string 
	 */

	private function draw_svg_ave_lines($month, $ave_this_year, $year){
		$month = intval($month);
		$ave_this_year = intval($ave_this_year);
		
		$yvalue = $this->axisheight - $ave_this_year;
		
		if(11 < $this->startingmonth-$month){
			if(0 != $month){
				$x_start = ($this->startingmonth-$month - 12  + 1.5 )*$this->monthwidth;
			}else{
				$x_start = ($this->startingmonth - date("n") + 1.5 ) * $this->monthwidth;
			}
		}else{
			$x_start = $this->monthwidth / 2;
		}
		$x_end = ($this->startingmonth-$month + 1.5)*$this->monthwidth;
		
		$ytext = $yvalue -20;
		$xtext = $x_start + 10;
		
		$this->main_graph_ave_lines = "\t<text x=\"$xtext\" y=\"$ytext\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"$this->average_graphline_color\">$ave_this_year</text>\n";
		$this->main_graph_ave_lines .= "\t<path stroke=\"$this->average_graphline_color\" stroke-dasharray=\"5, 5\"  d=\"M$x_start $yvalue H$x_end\"/>\n";
		if(0 != $month){
			$this->main_graph_ave_lines .= "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M$x_end 0 v $this->axisheight\"/>\n";
		}
		$this->main_graph_ave_lines .= "\t<text x=\"$xtext\" y=\"20\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"$this->axes_color\">$year</text>\n";
		return $this->main_graph_ave_lines;
	}
	
	
	
	
	
	
	
	/**
	 * Draw the x and y axes of the graph
	 *
	 * @return string the axes of the graph
	 */
	private function draw_axes(){
		# X axis
		$this->display_graph_axis = "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M5 $this->axisheight h $this->axiswidth\"/>\n";
		# Y axis
		$this->display_graph_axis .= "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M5 0 v $this->axisheight\"/>\n";
		
		return $this->display_graph_axis;
	}
	
	
	
	
	
}

?>