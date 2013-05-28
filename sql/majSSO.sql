ALTER TABLE llx_zenfusion_oauth ADD oauth_id VARCHAR(255);
ALTER TABLE llx_zenfusion_oauth CHANGE token token TEXT;
