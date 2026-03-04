/**
 * Internal dependencies
 */
import config from '../../../../../playwright.config';
import {RequestUtils} from "@wordpress/e2e-test-utils-playwright";

export async function setPageContent(  requestUtils: RequestUtils, postId: number, postContent: string ) {
	const pages = await requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/pages/' + postId,
		data: {
			content: postContent,
		},
	} );

	return;
}

export async function getPostContentRendered(
	postType: string,
	postId: number
): Promise< string > {
	const baseURL: string = config.use.baseURL;
	const fullUrl = baseURL + '/wp-json/wp/v2/' + postType + 's/' + postId;

	const response: Response = await fetch( fullUrl );

	const result = await response.json();

	return result.content.rendered;
}
