<?php
/**
 * Cavalcade Runner
 */

namespace HM\Cavalcade\Runner;

class Worker {
	public $process;
	public $pipes = [];
	public $job;

	protected $output = '';
	protected $error_output = '';
	protected $status = null;
	protected $debug = false;

	public function __construct( $process, $pipes, Job $job, $debug = false ) {
		$this->process = $process;
		$this->pipes = $pipes;
		$this->job = $job;
		$this->debug = $debug;
	}

	public function is_done() {
		if ( isset( $this->status['running'] ) && ! $this->status['running'] ) {
			// Already exited, so don't try and fetch again
			// (Exit code is only valid the first time after it exits)
			return ! ( $this->status['running'] );
		}

		$this->status = proc_get_status( $this->process );
		if ( $this->debug ) {
			printf( '[%d] Worker status: %s' . PHP_EOL, $this->job->id, print_r( $this->status, true ) );
		}
		return ! ( $this->status['running'] );
	}

	/**
	 * Drain stdout & stderr into properties.
	 *
	 * Draining the pipes is needed to avoid workers hanging when they hit the system pipe buffer limits.
	 */
	public function drain_pipes() {
		while ( $data = fread( $this->pipes[1], 1024 ) ) {
			$this->output .= $data;
		}

		while ( $data = fread( $this->pipes[2], 1024 ) ) {
			$this->error_output .= $data;
		}
	}

	/**
	 * Shut down the process
	 *
	 * @return bool Did the process run successfully?
	 */
	public function shutdown() {
		if ( $this->debug ) {
			printf( '[%d] Worker shutting down...' . PHP_EOL, $this->job->id );
		}

		// Exhaust the streams
		$this->drain_pipes();
		fclose( $this->pipes[1] );
		fclose( $this->pipes[2] );

		// Minimize output made by Runner - only output when not empty
		if ( $this->debug ) {
			if ( ! empty($this->output) ) {
				printf( '[%d] Worker output: %s' . PHP_EOL, $this->job->id, $this->output );
			}

			if ( ! empty($this->error_output) ) {
				printf( '[%d] Worker errors: %s' . PHP_EOL, $this->job->id, $this->error_output );
			}

			if ( ! empty($this->status['exitcode']) ) {
				printf( '[%d] Worker exitcode: %d' . PHP_EOL, $this->job->id, $this->status['exitcode'] );
			}
		} else {
			if ( $this->status['exitcode'] === 0 ) {
				printf( '[%d] Task "%s" completed successfully.' . PHP_EOL, $this->job->id, $this->job->hook );
			} else {
				printf( '[%d] Task "%s" Failed. Exit code: %s.' . PHP_EOL, $this->job->id, $this->job->hook, $this->status['exitcode'] );
			}
		}

		/* printf( '[%d] Worker output: %s' . PHP_EOL, $this->job->id, $this->output );
		printf( '[%d] Worker errors: %s' . PHP_EOL, $this->job->id, $this->error_output );
		printf( '[%d] Worker exitcode: %d' . PHP_EOL, $this->job->id, $this->status['exitcode'] ); */

		// Close the process down too
		proc_close( $this->process );
		unset( $this->process );

		return ( $this->status['exitcode'] === 0 );
	}
}
