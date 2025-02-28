<?php
/**
 * View_Cache_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Http\View\View_Finder;
use Mantle\Support\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * View Cache Command
 */
class View_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'view:cache';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Compile all Blade templates in an application';

	/**
	 * View finder instance.
	 *
	 * @var View_Finder
	 */
	protected $finder;

	/**
	 * Blade compiler.
	 *
	 * @var \Illuminate\View\Compilers\BladeCompiler
	 */
	protected $blade;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Hide the command in isolation mode.
		if ( app()->is_running_in_console_isolation() ) {
			$this->setHidden( true );
		}
	}

	/**
	 * Compile all blade views.
	 *
	 * @param View_Finder $finder Finder instance.
	 */
	public function handle( View_Finder $finder ) {
		if ( ! isset( $this->container['view.engine.resolver'] ) ) {
			$this->error( 'Missing view engine resolver from the view service provider.' );
			return Command::FAILURE;
		}

		$this->blade  = $this->container['view.engine.resolver']->resolve( 'blade' )->getCompiler();
		$this->finder = $finder;

		$this->call( 'mantle view:clear' );

		$paths = $this->finder->get_paths();

		if ( empty( $paths ) ) {
			$this->error( 'No view paths found.' );
			return Command::FAILURE;
		}

		$this->compile_views( $this->blade_files_in( $paths ) );

		$this->log( 'Blade templates cached successfully.' );

		return Command::SUCCESS;
	}

	/**
	 * Compile all views from a collection.
	 *
	 * @param Collection $views Collection of view paths.
	 * @return void
	 */
	protected function compile_views( Collection $views ): void {
		$views->map(
			function ( SplFileInfo $file ) {
				$this->blade->compile( $file->getRealPath() );
			}
		);
	}

	/**
	 * Locate all blade files in a path.
	 *
	 * @param string[] $paths File path.
	 * @return Collection
	 */
	protected function blade_files_in( array $paths ): Collection {
		return collect(
			Finder::create()
				->in( $paths )
				->exclude( 'vendor' )
				->name( '*.blade.php' )
				->files()
		);
	}
}
