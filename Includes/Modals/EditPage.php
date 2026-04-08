<?php if (!isset($Page)) return; ?>

<div class="Modal EditPageModal hidden" id="EditPageModal">
    <div class="EditPageCard">
        <div class="EditPageHeader">
            <h2 class="EditPageTitle">Manage Page</h2>
            <button class="EditPageClose" id="EditPageClose"></button>
        </div>

        <div class="EditPageBody">

            <!-- Cover photo -->
            <div class="EditPageCoverWrap">
                <div class="EditPageCoverPreview <?php echo !$CoverSrc ? 'PageCoverDefault' : ''; ?>" id="EditPageCoverPreview">
                    <?php if ($CoverSrc): ?>
                        <img src="<?php echo $CoverSrc; ?>" alt="" id="EditPageCoverImg">
                    <?php else: ?>
                        <img src="" alt="" id="EditPageCoverImg" class="hidden">
                    <?php endif; ?>
                </div>
                <label class="EditPageMediaBtn" for="EditPageCoverUpload">Change Cover</label>
                <input type="file" id="EditPageCoverUpload" accept="image/*" class="hidden">
            </div>

            <!-- Logo -->
            <div class="EditPageLogoWrap">
                <div class="EditPageLogoPreview" id="EditPageLogoPreview">
                    <?php if ($LogoSrc): ?>
                        <img src="<?php echo $LogoSrc; ?>" alt="" id="EditPageLogoImg">
                    <?php else: ?>
                        <div class="PageLogoPlaceholder" id="EditPageLogoPlaceholder"><?php echo mb_strtoupper(mb_substr($Page['Name'], 0, 1)); ?></div>
                        <img src="" alt="" id="EditPageLogoImg" class="hidden">
                    <?php endif; ?>
                </div>
                <label class="EditPageMediaBtn" for="EditPageLogoUpload">Change Logo</label>
                <input type="file" id="EditPageLogoUpload" accept="image/*" class="hidden">
            </div>

            <div class="EditPageFieldGroup">
                <label class="EditPageLabel">Page Name</label>
                <input type="text" class="EditPageInput" id="EditPageNameInput" value="<?php echo htmlspecialchars($Page['Name']); ?>" maxlength="100">
            </div>

            <div class="EditPageFieldGroup">
                <label class="EditPageLabel">Handle</label>
                <div class="CreatePageHandleWrap">
                    <span class="CreatePageHandleAt">@</span>
                    <input type="text" class="EditPageInput" id="EditPageHandleInput" value="<?php echo htmlspecialchars($Page['Handle']); ?>" maxlength="50">
                </div>
                <span class="CreatePageHint" id="EditPageHandleHint">Only letters, numbers and underscores.</span>
            </div>

            <div class="EditPageFieldGroup">
                <label class="EditPageLabel">Category</label>
                <?php $Cat = $Page['Category'] ?? ''; ?>
                <select class="EditPageSelect" id="EditPageCategoryInput">
                    <option value="">Select a category…</option>
                    <optgroup label="Business">
                        <option <?php echo $Cat==='Local Business'?'selected':''; ?>>Local Business</option>
                        <option <?php echo $Cat==='Retail &amp; Shop'?'selected':''; ?>>Retail &amp; Shop</option>
                        <option <?php echo $Cat==='Restaurant &amp; Food'?'selected':''; ?>>Restaurant &amp; Food</option>
                        <option <?php echo $Cat==='Company &amp; Corporation'?'selected':''; ?>>Company &amp; Corporation</option>
                        <option <?php echo $Cat==='Startup'?'selected':''; ?>>Startup</option>
                        <option <?php echo $Cat==='Finance &amp; Banking'?'selected':''; ?>>Finance &amp; Banking</option>
                        <option <?php echo $Cat==='Real Estate'?'selected':''; ?>>Real Estate</option>
                        <option <?php echo $Cat==='Healthcare'?'selected':''; ?>>Healthcare</option>
                        <option <?php echo $Cat==='Education &amp; School'?'selected':''; ?>>Education &amp; School</option>
                        <option <?php echo $Cat==='Technology'?'selected':''; ?>>Technology</option>
                    </optgroup>
                    <optgroup label="Creator &amp; Brand">
                        <option <?php echo $Cat==='Brand &amp; Product'?'selected':''; ?>>Brand &amp; Product</option>
                        <option <?php echo $Cat==='Public Figure'?'selected':''; ?>>Public Figure</option>
                        <option <?php echo $Cat==='Artist &amp; Musician'?'selected':''; ?>>Artist &amp; Musician</option>
                        <option <?php echo $Cat==='Athlete &amp; Sports Team'?'selected':''; ?>>Athlete &amp; Sports Team</option>
                        <option <?php echo $Cat==='Media &amp; Entertainment'?'selected':''; ?>>Media &amp; Entertainment</option>
                        <option <?php echo $Cat==='News &amp; Media'?'selected':''; ?>>News &amp; Media</option>
                    </optgroup>
                    <optgroup label="Community">
                        <option <?php echo $Cat==='Community &amp; Group'?'selected':''; ?>>Community &amp; Group</option>
                        <option <?php echo $Cat==='Fan Page'?'selected':''; ?>>Fan Page</option>
                        <option <?php echo $Cat==='Non-profit &amp; Charity'?'selected':''; ?>>Non-profit &amp; Charity</option>
                        <option <?php echo $Cat==='Government &amp; Politics'?'selected':''; ?>>Government &amp; Politics</option>
                        <option <?php echo $Cat==='Religious Organisation'?'selected':''; ?>>Religious Organisation</option>
                    </optgroup>
                    <option value="Other" <?php echo $Cat==='Other'?'selected':''; ?>>Other</option>
                </select>
            </div>

            <div class="EditPageFieldGroup">
                <label class="EditPageLabel">Website</label>
                <input type="url" class="EditPageInput" id="EditPageWebsiteInput" value="<?php echo htmlspecialchars($Page['Website'] ?? ''); ?>" placeholder="https://example.com">
            </div>

            <div class="EditPageFieldGroup">
                <label class="EditPageLabel">Bio</label>
                <textarea class="EditPageTextarea" id="EditPageBioInput" maxlength="500" placeholder="Tell people what this page is about…"><?php echo htmlspecialchars($Page['Bio'] ?? ''); ?></textarea>
            </div>

            <div class="CreatePageError hidden" id="EditPageError"></div>

            <button class="BrandBtn CreatePageSubmitBtn" id="EditPageSubmit">Save Changes</button>
            <div class="Loader hidden" id="EditPageLoader"></div>
        </div>
    </div>
</div>
