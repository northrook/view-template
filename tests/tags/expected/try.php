<?php
%A%
		$__try__[0] = [$__it__ ?? null];
		ob_start(fn() => '');
		try /* line 1 */ {
			echo '	a
';
			throw new \Core\View\Template\Exception\RollbackException;
			echo '	b
';

		} catch (Throwable $__exception__) {
			ob_clean();
			if ( !( $__exception__ instanceof \Core\View\Template\Exception\RollbackException) && isset($this->global->coreExceptionHandler)) {
				($this->global->coreExceptionHandler)($__exception__, $this);
			}
			echo '	c
';

		} finally {
			echo ob_get_clean();
			$iterator = $__it__ = $__try__[0][0];
		}
%A%
