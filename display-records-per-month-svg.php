<?php
/**
 * GRAPHClass - counts the volume of records per month over x months and displays it as a SVG.
 * PHP Version 5.0.0
 * Version 0.1.1
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
 * ################
 * ##
 * ##  CONFIG
 * ##
 * ################
 */


/**
 * Specify host for MySQL database connection
 */
private $mysql_host = "localhost";

/**
 * Specify database name for MySQL database connection
 */
private $mysql_dbname = "";

/**
 * Specify username for MySQL database connection
 */
private $mysql_username = "";

/**
 * Specify password for MySQL database connection
 */
private $mysql_password = "";

/**
 * Specify the table on the database to count on the MySQL DB
 */
private $tablename = "";

/**
 * Specify the timestamp fieldname on the table on the MySQL DB, must be a timestamp!
 */
private $record_date = "";
	
/**
 * Specify how many months to go back from current month
 * 
 * 72 months = 6 years
 */ 
private $startingmonth= 72;

/**
 * Specify the maximum height of the graph in pixels, this overrides $yscale
 */ 
private $max_graph_height= 500;

/**
 * Specify the scale of the y-axis.
 *
 * Default = 1, higher means a taller graph
 */ 
private $yscale = 1;

/**
 * Specify the distance between points on the x-axis
 *
 * At the default number of months, 72, the default 
 * $monthwidth = 10 gives a x-axis length of around 730px
 */ 
private $monthwidth = 10;

/**
 * Specify the margin at the top of the SVG
 */
private $graphmargintop = 5;

/**
 * Specify the margin at the right of the SVG
 */
private $graphmarginright = 60;

/**
 * Specify the margin at the bottom of the SVG
 */
private $graphmarginbottom = 5;

/**
 * Specify the margin at the left of the SVG
 */
private $graphmarginleft = 5;

/**
 * Specify the colour of the main graph and points thereon
 */
private $main_graphline_color = "green";

/**
 * Specify the colour of the dashed "year average" graph and legend
 */
private $average_graphline_color = "blue";
/**
 * Specify the colour of the axes and plain text
 */
private $axes_color = "black";

/**
 * Specify the associated website URL
 */
public $siteurl = "";	

/**
 * ########################
 * ##
 * ##  CLASS VARIABLES
 * ##
 * ########################
 */

/**
 * The length of x axis in pixels is calculated with $monthwidth and $startingmonth
 */
private $axiswidth = 0;

/**
 * The length of y axis in pixels is calculated with $yscale and $max_month_value
 */
private $axisheight = 0;

/**
 * Specify the height of the whole SVG image
 */
private $graphheight = 0;

/**
 * Specify the width of the whole SVG image
 */
private $graphwidth = 0;

/**
 * This variable adds the volume of records from different months in the year
 */
private $monthaggregator = 0;

/**
 * Counts how many months have been counted this year
 */
private $count_months_recorded_this_year = 0;

/**
 * This variable holds the string with graph axes 
 */
private $display_graph_axis = "";

/**
 * This variable holds the main graph SVG
 */
private $display_main_graph = "";

/**
 * This variable holds linking dots on the main graph points
 */
private $main_graph_dots = "";

/**
 * This variable holds the year average graph lines
 */
private $main_graph_ave_lines = "";

/**
 * This variable is the temporary volume for the month
 */
private $monthlyrecordvolume = array();

/**
 * This variable is the temporary yearly month average
 */
private $ave_this_year = array();

/**
 * This variable is the position in pixels of the year's end along x axis
 */
private $x_year_end = 0;

/**
 * This variable is the position in pixels of the year's start along x axis
 */
private $x_year_start = 0;

/**
 * This variable is the position in pixels of the year text along x axis
 */
private $x_year_text = 0;

/**
 * This variable is the temporary search month, integer
 */
private $searchmonth = 0;

/**
 * This variable is the temporary search year, 4-digits
 */
private $searchyear = 0;

/**
 * This variable is the maximum volume of data in one month.
 * We get the length of the y-axis from this.
 */
private $max_month_value = 0;

/**
 * This is the output of the class
 */
public $display_svg_graph = "";


	
	/**
	 * Connect to MySQL via a new PDO
	 * 
	 * Function graph_display() creates the output of GRAPHClass in the form of string $display_svg_graph
	 * An error connecting to the database returns an error to the page.
	 */
	public function __construct(){
		try {
  			$this->db = new PDO("mysql:host=$this->mysql_host;dbname=$this->mysql_dbname;charset=utf8", "$this->mysql_username", "$this->mysql_password");
  			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->organize_data();
			$this->graph_display();
			return;
		} catch(PDOException $ex) {
			$this->display_svg_graph = "<p>Database connection error</p>";
			return;
		}
	}

	/**
	 * This is the main function that co-ordinates the gathering of data
	 * 
	 * 
	 * @return
	 */
	private function grab_data(){
		$y=0;
		for($i=$this->startingmonth;$i>=0;$i--){
			$this->searchmonth = date("n")-$i;
			$this->searchyear = date("Y");
			if(0 >= $this->searchmonth){
				while(0 >= $this->searchmonth){
					$this->searchmonth += 12;
					$this->searchyear--;
				}
			}
			
			$this->count_chrono_records();
			$this->monthlyrecordvolume[$i] = intval($this->volume_of_records_in_timerange);
			if(0==$this->max_month_value or $this->monthlyrecordvolume[$i] > $this->max_month_value){
				$this->max_month_value = $this->monthlyrecordvolume[$i];
			}
			$this->monthaggregator += $this->monthlyrecordvolume[$i];
			
			$this->count_months_recorded_this_year++;
			if(12 == $this->searchmonth or 0 == $i ){
				$this->ave_this_year[$y]['volume'] = intval($this->monthaggregator / $this->count_months_recorded_this_year);
				$this->ave_this_year[$y]['month'] = $i;
				$this->ave_this_year[$y]['year'] = $this->searchyear;
				$this->monthaggregator = 0;
				$this->count_months_recorded_this_year = 0;
				$y++;
			}
		}
		return;
	}
	
	/**
	 * This is the main function that displays the different graphs.
	 * 
	 * 
	 * @return 
	 */
	private function draw_graphs(){
		for($i=$this->startingmonth;$i>=0;$i--){
			$this->draw_svg_main_line($i);
			$this->draw_svg_main_dots($i);
		}
		$y=0;
		while($this->ave_this_year[$y]['year']){
			$this->draw_svg_ave_lines($y);
			$this->draw_year_lines($y);
			$y++;
		}
		return;
	}
	
	/**
	 * X- and y-axis lengths are calculated, if the y-axis is too big it is reduced 
	 * to the maximum and y-scale changed accordingly. Likewise, if the scale increases
	 * the graph size so that it exceeds the maximum height, the scale is reduced so
	 * that it fits within the limits.
	 *
	 * @return 
	 */
	private function adjust_graph_dimensions(){
		$this->axiswidth = ($this->startingmonth + 1.5) * $this->monthwidth;
		if($this->max_graph_height < $this->max_month_value){
			$this->yscale = $this->max_graph_height / $this->max_month_value;
		}
		$this->axisheight = $this->yscale * ($this->max_month_value + 20);
		if($this->max_graph_height < $this->axisheight){
			$this->yscale = $this->yscale * ($this->max_graph_height / $this->axisheight);
			$this->axisheight = $this->max_graph_height;
		}
		$this->graphheight = $this->axisheight + $this->graphmargintop + $this->graphmarginbottom;
		$this->graphwidth = $this->axiswidth + $this->graphmarginleft + $this->graphmarginright;
		
		return;
	}
	
	/**
	 * This function controls the class.
	 *
	 * @return
	 */
	private function organize_data(){
		$this->grab_data();
		$this->adjust_graph_dimensions();
		$this->draw_graphs();
		$this->draw_axes();
		return;
	}
	
	/**
	 * Finally, the axes and graphs are drawn and put into an SVG image.
	 *
	 * @return
	 */
	private function graph_display(){
		$this->display_svg_graph = "<svg id=\"graph\" width=\"" . $this->graphwidth . "px\" height=\"" . $this->graphheight . "px\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">\n";
		$this->display_svg_graph .= $this->display_graph_axis;
		$this->display_svg_graph .= $this->display_main_graph;
		$this->display_svg_graph .= $this->main_graph_ave_lines;
		$this->display_svg_graph .= $this->main_graph_dots;
		$this->display_svg_graph .= "</svg>\n";
		return;
	}
	
	/**
	 * Gets the date timestamp for the month beginning and ending then gets
	 * the volume of records for that month.
	 * 
	 * An error in running the SQL returns an error message to the page, 0 records continues as normal
	 *
	 * @return
	 */
	private function count_chrono_records(){
		$searchmonthnext = $this->searchmonth + 1;
		$start = mktime(0, 0, 0, $this->searchmonth, 1, $this->searchyear);
		$end = mktime(23, 59, 59, $searchmonthnext, 0, $this->searchyear);
		
		try {
  		  		$stmt = $this->db->prepare(" SELECT COUNT(*) FROM $this->tablename WHERE $this->record_date >= :start AND $this->record_date <= :end ");
				$stmt->bindValue(':start', $start, PDO::PARAM_INT);
				$stmt->bindValue(':end', $end, PDO::PARAM_INT);
				$stmt->execute();
				$this->volume_of_records_in_timerange = $stmt->fetchColumn();
		} catch(PDOException $ex) {
		    		$this->display_svg_graph = "<p>Unable to count records.</p>";
				return;
		}
		
		return;
	}
	
	/**
	 * Plots the main data on the graph joined up by a solid line
	 *
	 * @return 
	 */
	private function draw_svg_main_line($month){
		$yvalue = $this->axisheight - ($this->yscale * $this->monthlyrecordvolume[$month]);
		$xvalue = ($this->startingmonth - $month + 1)*$this->monthwidth;
		
		if($month == $this->startingmonth){
			$this->display_main_graph = "\t<path d=\"M$xvalue $yvalue ";
		}elseif(0 == $month){
			$this->display_main_graph .= "L$xvalue $yvalue\" stroke-linejoin=\"round\" stroke=\"$this->main_graphline_color\" fill=\"none\"/>\n";
		}else{
			$this->display_main_graph .= "L$xvalue $yvalue ";
		}
		
		return;
	}
	
  /**
	 * Draws a dot on each point on the graph, the dots would link to the full list of 
	 * records
	 *
	 * @return 
	 */
	private function draw_svg_main_dots($month){
		$yvalue = $this->axisheight - ($this->yscale * $this->monthlyrecordvolume[$month]);
		$xvalue = ($this->startingmonth-$month)*$this->monthwidth + $this->monthwidth;
		
		$this->main_graph_dots .= "<a xlink:href=\"#\" xlink:title=\"{$this->monthlyrecordvolume[$month]}\" ><circle cx=\"$xvalue\" cy=\"$yvalue\" r=\"2\" stroke=\"$this->main_graphline_color\" fill=\"$this->main_graphline_color\" stroke-width=\"1\" /></a>\n";
		
		return;
	}
	
	/**
	 * This method draws the dotted red lines that are the average volume per month,
	 * and write the average value in red above the line. 
	 *
	 * @return 
	 */
	private function draw_svg_ave_lines($y){
		$yvalue = $this->axisheight - ($this->yscale * $this->ave_this_year[$y]['volume']);
		
		if(11 < $this->startingmonth - $this->ave_this_year[$y]['month']){
			if(0 != $this->ave_this_year[$y]['month']){
				$this->x_year_start = ($this->startingmonth - $this->ave_this_year[$y]['month'] - 12  + 1.5 )*$this->monthwidth;
			}else{
				$this->x_year_start = ($this->startingmonth - date("n") + 1.5 ) * $this->monthwidth;
			}
		}else{
			$this->x_year_start = $this->monthwidth / 2;
		}
		$this->x_year_end = ($this->startingmonth-$this->ave_this_year[$y]['month'] + 1.5)*$this->monthwidth;
		
		$ytext = $yvalue -20;
		$this->x_year_text = $this->x_year_start + 10;
		
		$this->main_graph_ave_lines .= "\t<text x=\"$this->x_year_text\" y=\"$ytext\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"$this->average_graphline_color\">{$this->ave_this_year[$y]['volume']}</text>\n";
		$this->main_graph_ave_lines .= "\t<path stroke=\"$this->average_graphline_color\" stroke-dasharray=\"5, 5\"  d=\"M$this->x_year_start $yvalue H$this->x_year_end\"/>\n";
		return;
	}
	
	/**
	 * This method tidies up by drawing the year and a vertical dotted line
	 *  to separate one year from another.
	 *
	 * @return 
	 */
	private function draw_year_lines($y){
		if(0 != $this->ave_this_year[$y]['month']){
			$this->main_graph_ave_lines .= "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M$this->x_year_end 0 v $this->axisheight\"/>\n";
		}
		$this->main_graph_ave_lines .= "\t<text x=\"$this->x_year_text\" y=\"20\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"$this->axes_color\">{$this->ave_this_year[$y]['year']}</text>\n";
		return;
	}
	
	/**
	 * Draw the x and y axes of the graph
	 *
	 * @return
	 */
	private function draw_axes(){
		$this->display_graph_axis = "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M$this->graphmarginleft $this->axisheight h $this->axiswidth\"/>\n";
		$this->display_graph_axis .= "\t<path stroke=\"$this->axes_color\" stroke-dasharray=\"5, 5\"  d=\"M$this->graphmargintop 0 v $this->axisheight\"/>\n";
		return;
	}
	
	
	
	
	
}

?>
