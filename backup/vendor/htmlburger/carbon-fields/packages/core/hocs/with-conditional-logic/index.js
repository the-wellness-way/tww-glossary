/* eslint no-console: [ 'error', { allow: [ 'error' ] } ] */

/**
 * External dependencies.
 */
import { createHigherOrderComponent, compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { withEffects } from 'refract-callbag';
import { __, sprintf } from '@wordpress/i18n';
import {
	has,
	find,
	some,
	every,
	isEmpty
} from 'lodash';
import empty from 'callbag-empty';

/**
 * Internal dependencies.
 */
import compare from './compare';

/**
 * Creates a high-order components which adds ability to evalute
 * the conditional logic of fields.
 *
 * @param  {Function} input
 * @param  {Function} output
 * @return {Function}
 */
export default function withConditionalLogic( input, output ) {
	/**
	 * The function that controls the stream of side-effects.
	 *
	 * @param  {Object} component
	 * @param  {Object} props
	 * @return {Object}
	 */
	function aperture( component, props ) {
		if ( isEmpty( props.field.conditional_logic ) ) {
			return empty;
		}

		return input( props, component );
	}

	/**
	 * The function that causes the side effects.
	 *
	 * @param  {Object} props
	 * @return {Function}
	 */
	function handler( props ) {
		return function( effect ) {
			let fieldExists = has( effect, props.name ) || find( effect, [ 'id', props.id ] );

			if ( typeof fieldExists === 'undefined' ) {
				/*
				 * Issue: When conditonal logic is used in Container
				 * it would return array of all fields with IDs, and other props,
				 * but when it is in Block container it only return the field name
				 * Fix: trim the field ID, leave only the field name and try to
				 * find the real field in effect by it.
				 * 
				 * Eg.: cf-43e66f51-560c-432f-8335-b866884a0b6d__new_hero_items__cf-4HrGgZu2fYEy8gUqLLXfo__external_url_bwt
				 * would become just "new_hero_items" in innerId[1]
				*/
				let innerId = /__(.*?)__/g.exec( props.id ); 
				if ( innerId && innerId.length && innerId[1] ) {
					fieldExists = has( effect, innerId[1] ) || find( effect, [ 'id', innerId[1] ] );
				}
			}

			if ( ! fieldExists ) {
				return;
			}

			const { relation, rules } = props.field.conditional_logic;
			const data = output( props, effect );

			const results = rules.reduce( ( accumulator, rule ) => {
				if ( ! has( data, rule.field ) ) {
					// eslint-disable-next-line
					console.error(
						sprintf(
							__( 'An unknown field is used in condition - "%s"', 'carbon-fields-ui' ),
							rule.field
						)
					);

					return accumulator.concat( false );
				}

				// TODO: Handle the conditional logic for chained fields. Probably we'll need the id of each sibling.
				// See https://github.com/htmlburger/carbon-fields/commit/3628a86c8840c8323f45c829a96c512a9985ad10#diff-b1aea524a4b1ab510e28e01a54c25fcd
				const result = compare( data[ rule.field ], rule.compare, rule.value );

				return accumulator.concat( result );
			}, [] );

			let isVisible = false;

			switch ( relation ) {
				case 'AND':
					isVisible = every( results );
					break;

				case 'OR':
					isVisible = some( results );
					break;
			}

			if ( isVisible ) {
				props.showField( props.id );
			} else {
				props.hideField( props.id );
			}
		};
	}

	return createHigherOrderComponent( ( OriginalComponent ) => {
		return compose(
			withDispatch( ( dispatch ) => {
				const { showField, hideField } = dispatch( 'carbon-fields/core' );

				return {
					showField,
					hideField
				};
			} ),
			withSelect( ( select, props ) => ( {
				visible: select( 'carbon-fields/core' ).isFieldVisible( props.id )
			} ) ),
			withEffects( aperture, { handler } )
		)( OriginalComponent );
	}, 'withConditionalLogic' );
}
