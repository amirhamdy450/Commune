<!-- Create Page Modal -->
<div class="Modal CreatePageModal hidden" id="CreatePageModal">
    <div class="CreatePageCard">
        <div class="CreatePageHeader">
            <div class="CreatePageHeaderText">
                <h2 class="CreatePageTitle">Create a Page</h2>
                <p class="CreatePageSub">For businesses, brands, communities, artists, local shops and more.</p>
            </div>
            <button class="CreatePageClose" id="CreatePageClose">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="CreatePageBody">
            <div class="CreatePageFieldGroup">
                <label class="CreatePageLabel">Page name <span class="CreatePageRequired">*</span></label>
                <input type="text" class="CreatePageInput" id="PageNameInput" placeholder="e.g. Acme Store, The Jazz Corner, FC Barcelona Fans…" maxlength="100">
            </div>

            <div class="CreatePageFieldGroup">
                <label class="CreatePageLabel">Handle <span class="CreatePageRequired">*</span></label>
                <div class="CreatePageHandleWrap">
                    <span class="CreatePageHandleAt">@</span>
                    <input type="text" class="CreatePageInput" id="PageHandleInput" placeholder="acme_store" maxlength="50">
                </div>
                <span class="CreatePageHint" id="PageHandleHint">Only letters, numbers and underscores.</span>
            </div>

            <div class="CreatePageFieldGroup">
                <label class="CreatePageLabel">Category</label>
                <select class="CreatePageInput CreatePageSelect" id="PageCategoryInput">
                    <option value="">Select a category…</option>
                    <optgroup label="Business">
                        <option>Local Business</option>
                        <option>Retail &amp; Shop</option>
                        <option>Restaurant &amp; Food</option>
                        <option>Company &amp; Corporation</option>
                        <option>Startup</option>
                        <option>Finance &amp; Banking</option>
                        <option>Real Estate</option>
                        <option>Healthcare</option>
                        <option>Education &amp; School</option>
                        <option>Technology</option>
                    </optgroup>
                    <optgroup label="Creator & Brand">
                        <option>Brand &amp; Product</option>
                        <option>Public Figure</option>
                        <option>Artist &amp; Musician</option>
                        <option>Athlete &amp; Sports Team</option>
                        <option>Media &amp; Entertainment</option>
                        <option>News &amp; Media</option>
                    </optgroup>
                    <optgroup label="Community">
                        <option>Community &amp; Group</option>
                        <option>Fan Page</option>
                        <option>Non-profit &amp; Charity</option>
                        <option>Government &amp; Politics</option>
                        <option>Religious Organisation</option>
                    </optgroup>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="CreatePageFieldGroup">
                <label class="CreatePageLabel">Website</label>
                <input type="url" class="CreatePageInput" id="PageWebsiteInput" placeholder="https://example.com" maxlength="255">
            </div>

            <div class="CreatePageFieldGroup">
                <label class="CreatePageLabel">Description</label>
                <textarea class="CreatePageInput CreatePageTextarea" id="PageBioInput" placeholder="Tell people what your page is about…" maxlength="300"></textarea>
            </div>

            <div class="CreatePageError hidden" id="CreatePageError"></div>

            <button class="BrandBtn CreatePageSubmitBtn" id="CreatePageSubmit">Create Page</button>
            <div class="Loader hidden" id="CreatePageLoader"></div>
        </div>
    </div>
</div>
