<?php

namespace Gregwar\GnuPlot;

class GnuPlot
{
	// path to gnuplot
	protected $gnuPlot = "/opt/bin/gnuplot";
	
	// font file definition
	protected $fontfile = '';
	
	// font size
	protected $fontsize = '';
	
    // CSV data file
    protected $csvFile = '';

	// The CSV separator
	protected $csvSeparator = ';';
	    
    // Values as an array
    protected $values = array();

    // Time format if X data is time
    protected $timeFormat = null;
    
    // Format of the x axis
    protected $xTimeFormat = null;

    // Display mode
    protected $mode = 'line';

    // Plot width
    protected $width = 1200;

    // Plot height
    protected $height = 800;

    // Was it already plotted?
    protected $plotted = false;

    // X Label
    protected $xlabel;

    // Y Label
    protected $ylabel;

    // Y2 Label
    protected $y2label;
    
    // Graph labels
    protected $labels;

    // Titles
    protected $titles;

    // Y range scale
    protected $yrange;
    
    // Y2 range scale
    protected $y2range;

    // Graph title
    protected $title;

    // Gnuplot process
    protected $process;
    protected $stdin;
    protected $stdout;

    public function __construct()
    {
        $this->reset();
        $this->openPipe();
    }
    
    public function __destruct()
    {
        $this->sendCommand('quit');
        proc_close($this->process);
    }

    public static function withGnuPlot($path)
    {
        $instance = new self();
    	$instance->setGnuPlot($path);
    	return $instance;
    }
    
    /**
     * Reset all the values
     */
    public function reset()
    {
        $this->values = array();
        $this->xlabel = null;
        $this->ylabel = null;
        $this->y2label = null;
        $this->labels = array();
        $this->titles = array();
        $this->yrange = null;
        $this->yrange = null;
        $this->title = null;
    }

	/**
	 * Sets the font
	 */
	 public function setFontfile($file, $fontsize)
	 {
	 	$this->fontfile = $file;
	 	$this->fontsize = $fontsize;
	 	return $this;
	 }

	/**
	 * Sets the path to GnuPlot
	 */
	 public function setGnuPlot($path)
	 {
	 	$this->gnuPlot = $path;
	 	return $this;
	 }
    
    /**
     * Sets the Y Range for values
     */
    public function setYRange($min, $max)
    {
        $this->yrange = array($min, $max);

        return $this;
    }

    /**
     * Sets the Y2 Range for values
     */
    public function setY2Range($min, $max)
    {
        $this->y2range = array($min, $max);

        return $this;
    }

    /**
     * set seaparator for CSV file
     */
     public function setCSVSeparator($separator)
    {
    	$this->csvSeparator = $separator;
    	return $this;
    }
        
    /**
     * Set the CSV file
     */
     public function setCSVFile($file)
     {
     	$this->csvFile = $file;
     	return $this;
     }

    /**
     * Push a new data, $x is a number, $y can be a number or an array
     * of numbers
     */
    public function push($x, $y, $index = 0)
    {
        if (!isset($this->values[$index])) {
            $this->values[$index] = array();
        }

        $this->values[$index][] = array($x, $y);

        return $this;
    }

    /**
     * Sets the title of the $index th curve in the plot
     */
    public function setTitle($index, $title)
    {
        $this->titles[$index] = $title;

        return $this;
    }

    /**
     * Sets the graph width
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Sets the graph height
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Sets the graph title
     */
    public function setGraphTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Create the pipe
     */
    protected function sendInit()
    {
        $this->sendCommand('set grid');

        if ($this->title) {
            $this->sendCommand('set title "'.$this->title.'"');
        }

        if ($this->xlabel) {
            $this->sendCommand('set xlabel "'.$this->xlabel.'"');
        }

        if ($this->timeFormat) {
            $this->sendCommand('set xdata time');
            $this->sendCommand('set timefmt "'.$this->timeFormat.'"');
            if ($this->xTimeFormat) {
            	$this->sendCommand('set format x "'.$this->xTimeFormat.'"');
            } else {
	            $this->sendCommand('set format x "'.$this->timeFormat.'"');
	        }
            //$this->sendCommand('set xtics rotate by 45 offset -6,-3');
        }
        
        if ($this->ylabel) {
            $this->sendCommand('set ylabel "'.$this->ylabel.'"');
        }
        
        if ($this->y2label) {
            $this->sendCommand('set y2label "'.$this->y2label.'"');
        }

        if ($this->yrange) {
            $this->sendCommand('set yrange ['.$this->yrange[0].':'.$this->yrange[1].']');
        }
        
        if ($this->y2range) {
            $this->sendCommand('set y2range ['.$this->y2range[0].':'.$this->y2range[1].']');
        }

        foreach ($this->labels as $label) {
            $this->sendCommand('set label "'.$label[2].'" at '.$label[0].', '.$label[1]);
        }
    }

    /**
     * Runs the plot to the given pipe
     */
    public function plot($replot = false)
    {
        if ($replot) {
            $this->sendCommand('replot');
        } else {
            $this->sendCommand('plot '.$this->getUsings());
        }
        $this->plotted = true;
        $this->sendData();
    }
    
    /**
     * write PNG from CSV data
     */
    public function writePngFromCSV($file)
    {
        $this->sendInit();
        $this->sendCommand('set xtics nomirror');
        $this->sendCommand('set ytics nomirror');
		$this->sendCommand('set y2tics');
		$this->sendCommand('set tics out');
        
        $command = '';
        if ($this->fontfile) {
        	$command = 'font "' . $this->fontfile . '" ' . $this->fontsize;
        }

        $this->sendCommand('set terminal png ' . $command . ' size ' . $this->width . ',' . $this->height);
        
        $this->sendCommand('set datafile separator "' . $this->csvSeparator . '"');
        $this->sendCommand('set output "' . $file . '"');
		$this->sendCommand('plot "' . $this->csvFile . '" using 1:2 axes x1y1 smooth bezier with lines title columnhead,\'\' using 1:3 axes x1y2 smooth bezier with lines title columnhead');
		$this->plotted = true;
		$this->sendData();
    }

    /**
     * Write the current plot to a file
     */
    public function writePng($file)
    {
        $this->sendInit();
        $this->sendCommand('set xtics');
        $command = '';
        if ($this->fontfile) {
        	$command = 'font "' . $this->fontfile . '" ' . $this->fontsize;
        }

        $this->sendCommand('set terminal png ' . $command . ' size ' . $this->width . ',' . $this->height);
        $this->sendCommand('set output "'.$file.'"');
        $this->plot();
    }

    /**
     * Write the current plot to a file
     */
    public function get()
    {
        $this->sendInit();
        $this->sendCommand('set terminal png size '.$this->width.','.$this->height);
        fflush($this->stdout);
        $this->plot();

        // Reading data, timeout=100ms
        $result = '';
        $timeout = 100;
        do {
            stream_set_blocking($this->stdout, false);
            $data = fread($this->stdout, 128);
            $result .= $data;
            usleep(5000);
            $timeout-=5;
        } while ($timeout>0 || $data);

        return $result;
    }

    /**
     * Display the plot
     */
    public function display()
    {
        $this->sendInit();
        $this->plot();
    }

    /**
     * Refresh the rendering of the given pipe
     */
    public function refresh()
    {
        if ($this->plotted) {
            $this->plot(true);
        } else {
            $this->display();
        }
    }

    /**
     * Sets the label for X axis
     */
    public function setXLabel($xlabel)
    {
        $this->xlabel = $xlabel;

        return $this;
    }

    /**
     * Sets the X timeformat
     */
    public function setXTimeFormat($timeFormat)
    {
        $this->xTimeFormat = $timeFormat;

        return $this;
    }
    
    /**
     * Sets the X timeformat
     */
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * Sets the label for Y axis
     */
    public function setYLabel($ylabel)
    {
        $this->ylabel = $ylabel;

        return $this;
    }


    /**
     * Sets the label for Y2 axis
     */
    public function setY2Label($y2label)
    {
        $this->y2label = $y2label;

        return $this;
    }
    
    /**
     * Add a label text
     */
    public function addLabel($x, $y, $text)
    {
        $this->labels[] = array($x, $y, $text);

        return $this;
    }

    /**
     * Histogram mode
     */
    public function enableHistogram()
    {
        $this->mode = 'impulses linewidth 10';

        return $this;
    }

    /**
     * Gets the "using" line
     */
    protected function getUsings()
    {
        $usings = array();

        for ($i=0; $i<count($this->values); $i++) {
            $using = '"-" using 1:2 with '.$this->mode;
            if (isset($this->titles[$i])) {
                $using .= ' title "'.$this->titles[$i].'"';
            }
            $usings[] = $using;
        }

        return implode(', ', $usings);
    }

    /**
     * Sends all the command to the given pipe to give it the
     * current data
     */
    protected function sendData()
    {
        foreach ($this->values as $index => $data) {
            foreach ($data as $xy) {
                list($x, $y) = $xy;
                $this->sendCommand($x.' '.$y);
            }
            $this->sendCommand('e');
        }
    }

    /**
     * Sends a command to the gnuplot process
     */
    protected function sendCommand($command)
    {
        $command .= "\n";
        fwrite($this->stdin, $command);
    }

    /**
     * Open the pipe
     */
    protected function openPipe()
    {
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $this->process = proc_open($this->gnuPlot, $descriptorspec, $pipes);

        if (!is_resource($this->process)) {
            throw new Exception('Unable to run GnuPlot');
        }
        
        stream_set_blocking($pipes[2], 0);
        
        if ($err = stream_get_contents($pipes[2]))
    	{
      		throw new Swift_Transport_TransportException(
        		'Process could not be started [' . $err . ']'
        	);
    	}

        $this->stdin = $pipes[0];
        $this->stdout = $pipes[1];
    }
}
