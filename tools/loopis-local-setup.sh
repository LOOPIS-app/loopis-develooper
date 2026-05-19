#!/usr/bin/env bash
#
# LOOPIS Local Setup Script (LLSS)
#
# Terminal script for setting up a LOOPIS development environment locally.
#
# Version: 0.01
# Author: CoPilot (prompted by joxyzan)
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKSPACE_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

THEMES=(
	"loopis-theme"
	"loopis-theme-hq"
)

PLUGINS=(
	"loopis-admin"
	"loopis-config"
	"loopis-content"
	"loopis-cronjobs"
	"loopis-develooper"
	"loopis-users"
)

MU_PLUGINS=(
	"mu-plugins"
)

link_mu_plugins_folder() {
	local source_dir="$1"
	local target_path="$2"

	if [[ ! -d "$source_dir" ]]; then
		warn "MU plugins source folder not found, skipping: ${source_dir}"
		return
	fi

	if [[ -L "$target_path" ]]; then
		local current_target
		current_target="$(readlink "$target_path")"
		if [[ "$current_target" == "$source_dir" ]]; then
			info "Symlink already correct: ${target_path} -> ${source_dir}"
			return
		fi
		backup_path "$target_path"
	elif [[ -e "$target_path" ]]; then
		backup_path "$target_path"
	fi

	ln -s "$source_dir" "$target_path"
	info "Linked: ${target_path} -> ${source_dir}"
}

print_header() {
	cat <<'EOF'

      ========        ++++++++      
    ============    ++++++++++++    
   ======  =======++++++   +++++++  
  =====      =====+++++      +++++  
 =====         ====++         +++++ 
 =====         =====+         +++++ 
 =====        +++=====        +++++ 
  =====      +++++=====      +++++  
   ======  ++++++++==++++  ++++++   
    =======+++++    ++++++++++++    
      ========        ++++++++      

   –––––––––––––––––––––––––––––
   | LOOPIS LOCAL SETUP SCRIPT |
   –––––––––––––––––––––––––––––

This script will:
1. Create symlinks for the LOOPIS components in your local WP install.
2. Configure wp-config.php with LOOPIS debug/test settings.

Expected directory structure:
  /your-loopis-workspace/
  ├── develooper-tools/
  │   └── loopis-local-setup.sh
  ├── loopis-admin/
  ├── loopis-config/
  ├── loopis-content/
  ├── loopis-cronjobs/
  ├── loopis-develooper/
  ├── loopis-theme/
  ├── loopis-theme-hq/
  ├── loopis-users/
  └── mu-plugins/

How to use:
1. Create a local WP installation: https://github.com/LOOPIS-app/.github/blob/main/resources/local-setup.md
2. Ensure your directory is structured as above.
3. Run the script and follow the instructions.
EOF
	echo
}

info() {
	printf '[INFO] %s\n' "$1"
}

warn() {
	printf '[WARN] %s\n' "$1"
}

error_exit() {
	printf '[ERROR] %s\n' "$1" >&2
	exit 1
}

normalize_path_input() {
	local raw="$1"

	# Trim leading/trailing whitespace from pasted input.
	raw="${raw#"${raw%%[![:space:]]*}"}"
	raw="${raw%"${raw##*[![:space:]]}"}"

	# Remove matching surrounding quotes if present.
	if [[ "$raw" == \"*\" && "$raw" == *\" ]]; then
		raw="${raw:1:${#raw}-2}"
	elif [[ "$raw" == \'*\' && "$raw" == *\' ]]; then
		raw="${raw:1:${#raw}-2}"
	fi

	# Convert macOS drag-and-drop escaped spaces and common escaped characters.
	raw="${raw//\\ / }"
	raw="${raw//\\(/(}"
	raw="${raw//\\)/)}"

	printf '%s' "$raw"
}

confirm() {
	local prompt="$1"
	local answer
	read -r -p "${prompt} [y/N]: " answer
	[[ "${answer}" =~ ^[Yy]$ ]]
}

backup_path() {
	local path="$1"
	local backup
	backup="${path}.backup.$(date +%Y%m%d-%H%M%S)"
	mv "$path" "$backup"
	warn "Existing path moved to: ${backup}"
}

link_component() {
	local source="$1"
	local target="$2"

	if [[ ! -e "$source" ]]; then
		warn "Component not found, skipping: ${source}"
		return
	fi

	if [[ -L "$target" ]]; then
		local current_target
		current_target="$(readlink "$target")"
		if [[ "$current_target" == "$source" ]]; then
			info "Symlink already correct: ${target} -> ${source}"
			return
		fi
		backup_path "$target"
	elif [[ -e "$target" ]]; then
		backup_path "$target"
	fi

	ln -s "$source" "$target"
	info "Linked: ${target} -> ${source}"
}

setup_symlinks() {
	local wp_root="$1"
	local themes_dir="${wp_root}/wp-content/themes"
	local plugins_dir="${wp_root}/wp-content/plugins"
	local mu_plugins_path="${wp_root}/wp-content/mu-plugins"

	mkdir -p "$themes_dir" "$plugins_dir"

	info 'Linking themes...'
	for component in "${THEMES[@]}"; do
		link_component "${WORKSPACE_ROOT}/${component}" "${themes_dir}/${component}"
	done

	info 'Linking plugins...'
	for component in "${PLUGINS[@]}"; do
		link_component "${WORKSPACE_ROOT}/${component}" "${plugins_dir}/${component}"
	done

	info 'Linking mu-plugins...'
	for component in "${MU_PLUGINS[@]}"; do
		link_mu_plugins_folder "${WORKSPACE_ROOT}/${component}" "${mu_plugins_path}"
	done
}

ensure_wp_debug_constants() {
	local wp_config="$1"
	local tmp_file
	tmp_file="${wp_config}.tmp.$RANDOM"
	local changed=0
	local line

	: > "$tmp_file"

	while IFS= read -r line || [[ -n "$line" ]]; do
		if [[ "$line" == *"define( 'WP_DEBUG', false );"* ]]; then
			printf '%s\n' "define('WP_DEBUG', true);" >> "$tmp_file"
			printf '%s\n' "define('WP_DEBUG_LOG', true);" >> "$tmp_file"
			printf '%s\n' "define('WP_DEBUG_DISPLAY', false);" >> "$tmp_file"
			changed=1
			continue
		fi

		printf '%s\n' "$line" >> "$tmp_file"
	done < "$wp_config"

	mv "$tmp_file" "$wp_config"
	if [[ $changed -eq 1 ]]; then
		info 'Replaced default WP_DEBUG definition with local debug defines.'
	else
		info 'Default WP_DEBUG false definition not found (left unchanged).'
	fi
}

ensure_wp_config_block() {
	local wp_config="$1"
	local marker_start='# LOOPIS SETUP'
	local marker_end='# END LOOPIS SETUP'
	local stop_marker="/* That's all, stop editing! Happy publishing. */"

	# 1) Remove old LOOPIS block.
	local cleaned_file
	cleaned_file="${wp_config}.clean.$RANDOM"
	: > "$cleaned_file"
	local in_old_block=0
	local line
	while IFS= read -r line || [[ -n "$line" ]]; do
		if [[ "$line" == "$marker_start" ]]; then
			in_old_block=1
			continue
		fi
		if [[ $in_old_block -eq 1 ]]; then
			if [[ "$line" == "$marker_end" ]]; then
				in_old_block=0
			fi
			continue
		fi
		printf '%s\n' "$line" >> "$cleaned_file"
	done < "$wp_config"

	# 2) Insert the new LOOPIS block before the WordPress stop marker.
	local tmp_file
	tmp_file="${wp_config}.tmp.$RANDOM"
	: > "$tmp_file"

	if grep -Fq "$stop_marker" "$cleaned_file"; then
		local inserted=0
		while IFS= read -r line || [[ -n "$line" ]]; do
			if [[ $inserted -eq 0 && "$line" == *"$stop_marker"* ]]; then
				cat >> "$tmp_file" <<'EOF'
# LOOPIS SETUP
define('WP_MEMORY_LIMIT', '512M');
define('WP_LIVE', false);
define('WP_TEST', true);
# END LOOPIS SETUP
EOF
				printf '%s\n' "" >> "$tmp_file"
				printf '%s\n' "" >> "$tmp_file"
				inserted=1
			fi
			printf '%s\n' "$line" >> "$tmp_file"
		done < "$cleaned_file"
	else
		cp "$cleaned_file" "$tmp_file"
		cat >> "$tmp_file" <<'EOF'
# LOOPIS SETUP
define('WP_MEMORY_LIMIT', '512M');
define('WP_LIVE', false);
define('WP_TEST', true);
# END LOOPIS SETUP
EOF
		printf '%s\n' "" >> "$tmp_file"
		printf '%s\n' "" >> "$tmp_file"
	fi

	rm -f "$cleaned_file"
	mv "$tmp_file" "$wp_config"
	info 'Inserted/updated LOOPIS setup block in wp-config.php.'
}

main() {
	print_header

	info "Workspace root detected as: ${WORKSPACE_ROOT}"
	local wp_root
	read -r -p "Enter your local WordPress root path (containing wp-config.php): " wp_root

	wp_root="$(normalize_path_input "$wp_root")"

	if [[ -z "${wp_root}" ]]; then
		error_exit 'No path provided.'
	fi

	wp_root="${wp_root/#\~/$HOME}"
	wp_root="$(cd "$wp_root" 2>/dev/null && pwd || true)"

	[[ -n "$wp_root" ]] || error_exit 'Provided path is invalid or not accessible.'
	[[ -f "${wp_root}/wp-config.php" ]] || error_exit "wp-config.php not found in: ${wp_root}"
	[[ -d "${wp_root}/wp-content" ]] || error_exit "wp-content not found in: ${wp_root}"

	info "Target WordPress root: ${wp_root}"
	if ! confirm 'Proceed with creating symlinks and editing wp-config.php?'; then
		info 'Cancelled by user.'
		exit 0
	fi

	setup_symlinks "$wp_root"
	ensure_wp_debug_constants "${wp_root}/wp-config.php"
	ensure_wp_config_block "${wp_root}/wp-config.php"

	echo
	info '☑ LOOPIS local setup completed successfully.'
	info '∞ You can now start developing and testing your changes locally.'
}

main "$@"