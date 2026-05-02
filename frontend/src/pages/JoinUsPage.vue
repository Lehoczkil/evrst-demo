<script setup lang="ts">
import { useForm } from 'vue-formify';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import {
  MemberApplicationRequests,
  type MemberApplicationPayload,
} from '@/services/requests/MemberApplicationRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const toast = useToast();

const EDUCATION_OPTIONS = ['BSc', 'MSc', 'PhD'] as const;
const LANGUAGE_OPTIONS = ['Hungarian', 'English', 'German'] as const;
const DEPARTMENT_OPTIONS = [
  'Marketing & Design',
  'Electronics & Software Development',
  'Propulsion',
  'Structure & Aerodynamics',
  'Management',
] as const;

interface FormState {
  email: string;
  name: string;
  university: string;
  education: string;
  faculty: string;
  why: string;
  hours: string;
  languages: string[];
  otherLanguage: string;
  department: string;
  tasks: string;
  skills: string;
}

const initialState: FormState = {
  email: '',
  name: '',
  university: '',
  education: '',
  faculty: '',
  why: '',
  hours: '',
  languages: [],
  otherLanguage: '',
  department: '',
  tasks: '',
  skills: '',
};

const { Form, values, reset } = useForm<FormState>({ initialValues: initialState });
const status = ref<'idle' | 'submitting' | 'success' | 'error'>('idle');

const plusKeys = [
  'join.plus.english',
  'join.plus.knowledge',
  'join.plus.hours',
  'join.plus.inPerson',
  'join.plus.tools',
  'join.plus.tdk',
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const onSubmit = async () => {
  status.value = 'submitting';

  const otherLanguage = (values.value.otherLanguage ?? '').trim();
  const languages = [
    ...(values.value.languages ?? []),
    ...(otherLanguage ? [`Other: ${otherLanguage}`] : []),
  ];

  const payload: MemberApplicationPayload = {
    email: values.value.email ?? '',
    name: values.value.name ?? '',
    university: values.value.university ?? '',
    education: values.value.education ?? '',
    faculty: values.value.faculty ?? '',
    why: values.value.why ?? '',
    hours: values.value.hours ?? '',
    languages,
    department: values.value.department ?? '',
    tasks: values.value.tasks ?? '',
    skills: values.value.skills ?? '',
  };

  try {
    await MemberApplicationRequests.submit(payload);
    status.value = 'success';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch {
    status.value = 'error';
    toast.add({
      severity: 'error',
      summary: t('join.error'),
      life: 4000,
    });
  }
};

const resetForm = () => {
  reset();
  status.value = 'idle';
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const submitting = computed(() => status.value === 'submitting');
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Toast />
  <HtmlTitle :title="t('join.title')" />

  <div class="container join">
    <div class="join__back">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="status === 'success'">
      <div class="join__success">
        <h2 class="join__success-title">{{ t('join.success.title') }}</h2>
        <p class="join__success-body">{{ t('join.success.body') }}</p>
        <SectionButton type="button" @click="resetForm">
          {{ t('join.success.again') }}
        </SectionButton>
      </div>
    </template>

    <template v-else>
      <div class="join__hero">
        <div class="join__hero-grid">
          <div>
            <span class="join__kicker">{{ t('join.tagline') }}</span>
            <h1 class="join__title">{{ t('join.title') }}</h1>
            <p class="join__intro">{{ t('join.intro') }}</p>
          </div>
          <div class="join__plus-card">
            <div class="join__plus-heading">{{ t('join.plus.heading') }}</div>
            <ul class="join__plus-list">
              <li v-for="key in plusKeys" :key="key" class="join__plus-item">
                <span class="join__plus-bullet">✓</span>
                <span>{{ t(key) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <Form @submit="onSubmit">
        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">01</span>
            <h2 class="join__section-title">{{ t('join.section.about') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.email') }}</label>
              <InputText v-model="values.email" type="email" required class="w-full" />
            </div>
            <div class="join__row-2">
              <div class="join__field">
                <label>{{ t('join.name') }}</label>
                <InputText v-model="values.name" required class="w-full" />
              </div>
              <div class="join__field">
                <label>{{ t('join.university') }}</label>
                <InputText v-model="values.university" required class="w-full" />
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.education') }}</label>
              <div class="join__option-grid">
                <label
                  v-for="opt in EDUCATION_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="values.education === opt"
                >
                  <RadioButton v-model="values.education" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.faculty') }}</label>
              <InputText v-model="values.faculty" required class="w-full" />
            </div>
          </div>
        </div>

        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">02</span>
            <h2 class="join__section-title">{{ t('join.section.availability') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.why') }}</label>
              <Textarea v-model="values.why" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.hours') }}</label>
              <small>{{ t('join.hoursHelp') }}</small>
              <InputText v-model="values.hours" required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.languages') }}</label>
              <div class="join__option-grid">
                <label
                  v-for="opt in LANGUAGE_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="(values.languages ?? []).includes(opt)"
                >
                  <Checkbox v-model="values.languages" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.languagesOther') }}</label>
              <InputText
                v-model="values.otherLanguage"
                :placeholder="t('join.languagesOtherPlaceholder')"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">03</span>
            <h2 class="join__section-title">{{ t('join.section.contribution') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.department') }}</label>
              <div class="join__option-grid join__option-grid--col">
                <label
                  v-for="opt in DEPARTMENT_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="values.department === opt"
                >
                  <RadioButton v-model="values.department" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.tasks') }}</label>
              <Textarea v-model="values.tasks" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.skills') }}</label>
              <Textarea v-model="values.skills" rows="3" auto-resize required class="w-full" />
            </div>
          </div>
        </div>

        <div class="join__submit-row">
          <SectionButton to="/">{{ t('button.home') }}</SectionButton>
          <SectionButton type="submit" :disabled="submitting">
            {{ submitting ? t('join.submitting') : t('join.submit') }}
          </SectionButton>
        </div>
      </Form>
    </template>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.join {
  padding-top: calc(var(--header-height) + 32px);
  padding-bottom: 64px;

  &__back {
    margin-bottom: 32px;
  }

  &__hero {
    position: relative;
    padding: clamp(28px, 4vw, 56px);
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 32px;
  }

  &__hero-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;

    @include media-up(md) {
      grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr);
      align-items: end;
    }
  }

  &__kicker {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(242, 172, 60, 0.18);
    color: var(--color-primary);
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 16px;
  }

  &__title {
    font-size: clamp(40px, 8vw, 84px);
    text-transform: uppercase;
    color: var(--color-primary);
    line-height: 0.96;
    font-family: var(--font-family-headline);
    margin: 0 0 16px;
  }

  &__intro {
    font-size: 17px;
    color: var(--color-dimmed);
    line-height: 1.6;
    max-width: 56ch;
  }

  &__plus-card {
    padding: 20px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--card-border);
    border-radius: 8px;
  }

  &__plus-heading {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-primary);
    margin-bottom: 12px;
    letter-spacing: 0.08em;
  }

  &__plus-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  &__plus-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 13px;
    line-height: 1.45;
  }

  &__plus-bullet {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    background: rgba(242, 172, 60, 0.22);
    color: var(--color-primary);
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    margin-top: 1px;
  }

  &__section {
    padding: clamp(24px, 3vw, 40px);
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    margin-bottom: 32px;
  }

  &__section-head {
    display: flex;
    align-items: baseline;
    gap: 16px;
    padding-bottom: 16px;
    border-bottom: 1px dashed var(--card-border);
    margin-bottom: 24px;
  }

  &__section-num {
    font-size: 32px;
    font-weight: 500;
    line-height: 1;
    background: linear-gradient(to right, transparent, var(--color-primary));
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
  }

  &__section-title {
    font-size: clamp(20px, 3vw, 28px);
    text-transform: uppercase;
    font-weight: 600;
    line-height: 1;
    margin: 0;
  }

  &__fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
      font-size: 14px;
      font-weight: 500;
    }

    small {
      font-size: 12px;
      color: var(--color-dimmed);
    }
  }

  &__row-2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;

    @include media-up(sm) {
      grid-template-columns: 1fr 1fr;
    }
  }

  &__option-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 8px;

    @include media-down(sm) {
      grid-template-columns: 1fr;
    }

    &--col {
      grid-template-columns: 1fr;
    }
  }

  &__option-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 150ms ease, background-color 150ms ease;

    &:hover {
      border-color: rgba(242, 172, 60, 0.6);
    }

    &[data-checked='true'] {
      border-color: var(--color-primary);
      background: rgba(242, 172, 60, 0.12);
    }
  }

  &__submit-row {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 8px;

    @include media-up(sm) {
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
    }
  }

  &__success {
    padding: clamp(32px, 4vw, 56px);
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    text-align: center;
  }

  &__success-title {
    font-size: clamp(28px, 5vw, 40px);
    text-transform: uppercase;
    margin: 0 0 16px;
  }

  &__success-body {
    font-size: 16px;
    color: var(--color-dimmed);
    line-height: 1.6;
    max-width: 520px;
    margin: 0 auto 24px;
  }
}
</style>
