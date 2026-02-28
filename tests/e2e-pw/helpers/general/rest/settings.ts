// returns json object of settings
/**
 * Internal dependencies
 */
import config from '../../../../../playwright.config';
import {expect, RequestUtils} from "@wordpress/e2e-test-utils-playwright";

async function getSettings(): Promise< object > {
	const baseURL: string = config.use.baseURL!;
	const fullUrl = `${ baseURL }/wp-json/wp/v2/settings`;

	const response: Response = await fetch( fullUrl );

	return await response.json();
}

export async function getSetting( name: string ): Promise< any > {
	const settings = await getSettings();

	return settings[ name ];
}

export async function updateSetting( requestUtils: RequestUtils, optionName: string, value: any ): Promise< void > {

	var data = {};
	data[optionName] = value;

	const response = await requestUtils.rest({
		method: 'POST',
		path: `/wp/v2/settings`,
		data: data,
	});
	expect( response.ok );
}
